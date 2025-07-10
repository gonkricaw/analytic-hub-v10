<?php

namespace App\Mail;

use App\Models\User;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * UserInvitationMail
 * 
 * Mailable class for sending user invitation emails.
 * Handles the email composition and delivery for new user invitations.
 */
class UserInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The user being invited
     */
    public User $user;

    /**
     * The administrator sending the invitation
     */
    public User $sender;

    /**
     * The temporary password for the user
     */
    public string $temporaryPassword;

    /**
     * Template data for the email
     */
    public array $templateData;

    /**
     * The email template to use
     */
    public ?EmailTemplate $emailTemplate;

    /**
     * Custom message from the administrator
     */
    public ?string $customMessage;

    /**
     * Whether this is a resend attempt
     */
    public bool $isResend;

    /**
     * Create a new message instance.
     * 
     * @param User $user The user being invited
     * @param User $sender The administrator sending the invitation
     * @param string $temporaryPassword The temporary password
     * @param array $templateData Template data for the email
     * @param EmailTemplate|null $emailTemplate The email template
     * @param string|null $customMessage Custom message from admin
     * @param bool $isResend Whether this is a resend attempt
     */
    public function __construct(
        User $user,
        User $sender,
        string $temporaryPassword,
        array $templateData = [],
        ?EmailTemplate $emailTemplate = null,
        ?string $customMessage = null,
        bool $isResend = false
    ) {
        $this->user = $user;
        $this->sender = $sender;
        $this->temporaryPassword = $temporaryPassword;
        $this->templateData = $templateData;
        $this->emailTemplate = $emailTemplate;
        $this->customMessage = $customMessage;
        $this->isResend = $isResend;

        // Set queue configuration
        $this->onQueue('invitations');
        $this->delay(now()->addSeconds(5)); // Small delay to ensure database consistency
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->emailTemplate?->subject ?? 'Welcome to Analytics Hub - Your Account Invitation';
        
        // Add resend indicator to subject if applicable
        if ($this->isResend) {
            $subject = '[RESEND] ' . $subject;
        }

        return Envelope::make(
            from: $this->getFromAddress(),
            to: [
                $this->user->email => $this->user->full_name
            ],
            subject: $subject,
            tags: ['invitation', 'user-management'],
            metadata: [
                'user_id' => $this->user->id,
                'sender_id' => $this->sender->id,
                'invitation_type' => $this->isResend ? 'resend' : 'initial',
                'template_id' => $this->emailTemplate?->id
            ]
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Use template if available, otherwise use default view
        if ($this->emailTemplate) {
            return Content::make(
                htmlView: 'emails.invitation.template',
                textView: 'emails.invitation.template-text',
                with: [
                    'user' => $this->user,
                    'sender' => $this->sender,
                    'temporaryPassword' => $this->temporaryPassword,
                    'templateData' => $this->templateData,
                    'customMessage' => $this->customMessage,
                    'isResend' => $this->isResend,
                    'htmlContent' => $this->renderTemplate($this->emailTemplate->body_html),
                    'textContent' => $this->renderTemplate($this->emailTemplate->body_text ?? '')
                ]
            );
        }

        return Content::make(
            view: 'emails.invitation.default',
            with: [
                'user' => $this->user,
                'sender' => $this->sender,
                'temporaryPassword' => $this->temporaryPassword,
                'templateData' => $this->templateData,
                'customMessage' => $this->customMessage,
                'isResend' => $this->isResend
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Handle a job failure.
     * 
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('User invitation email failed', [
            'user_id' => $this->user->id,
            'user_email' => $this->user->email,
            'sender_id' => $this->sender->id,
            'is_resend' => $this->isResend,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // You could also update the EmailQueue status here
        // or send a notification to administrators about the failure
    }

    /**
     * Get the "from" address for the email
     * 
     * @return array
     */
    private function getFromAddress(): array
    {
        $fromEmail = $this->emailTemplate?->from_email ?? config('mail.from.address');
        $fromName = $this->emailTemplate?->from_name ?? config('mail.from.name');

        return [$fromEmail => $fromName];
    }

    /**
     * Render template content with data
     * 
     * @param string $template
     * @return string
     */
    private function renderTemplate(string $template): string
    {
        $rendered = $template;
        
        foreach ($this->templateData as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $rendered = str_replace('{{' . $key . '}}', $value, $rendered);
            }
        }
        
        return $rendered;
    }

    /**
     * Configure the job.
     * 
     * @return void
     */
    public function configureJob(): void
    {
        $this->tries = 3;
        $this->timeout = 60;
        $this->retryAfter = 300; // 5 minutes
    }

    /**
     * Determine the time at which the job should timeout.
     * 
     * @return \DateTime
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(30);
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     * 
     * @param int $attempt
     * @return int
     */
    public function backoff(): array
    {
        return [60, 300, 900]; // 1 minute, 5 minutes, 15 minutes
    }
}