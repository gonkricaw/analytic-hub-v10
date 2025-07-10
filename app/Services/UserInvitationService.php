<?php

namespace App\Services;

use App\Models\User;
use App\Models\EmailTemplate;
use App\Models\EmailQueue;
use App\Models\UserActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

/**
 * UserInvitationService
 * 
 * Handles user invitation functionality for the Analytics Hub system.
 * Manages invitation sending, tracking, expiry, and resending capabilities.
 */
class UserInvitationService
{
    /**
     * Invitation expiry period in hours
     */
    const INVITATION_EXPIRY_HOURS = 168; // 7 days

    /**
     * Maximum resend attempts per invitation
     */
    const MAX_RESEND_ATTEMPTS = 3;

    /**
     * Send invitation email to a user
     * 
     * @param User $user The user to send invitation to
     * @param User $sender The administrator sending the invitation
     * @param string|null $customMessage Optional custom message
     * @param bool $isResend Whether this is a resend attempt
     * @return array Result with success status and details
     */
    public function sendInvitation(User $user, User $sender, ?string $customMessage = null, bool $isResend = false): array
    {
        try {
            DB::beginTransaction();

            // Validate user eligibility for invitation
            $validation = $this->validateInvitationEligibility($user, $isResend);
            if (!$validation['eligible']) {
                return [
                    'success' => false,
                    'message' => $validation['reason'],
                    'code' => 'VALIDATION_FAILED'
                ];
            }

            // Get invitation email template
            $template = $this->getInvitationTemplate();
            if (!$template) {
                return [
                    'success' => false,
                    'message' => 'Invitation email template not found',
                    'code' => 'TEMPLATE_NOT_FOUND'
                ];
            }

            // Generate or retrieve temporary password
            $tempPassword = $this->getTemporaryPassword($user);

            // Prepare template data
            $templateData = $this->prepareTemplateData($user, $sender, $tempPassword, $customMessage);

            // Create email queue entry
            $emailQueue = $this->queueInvitationEmail($user, $sender, $template, $templateData, $isResend);

            // Update user invitation tracking
            $this->updateInvitationTracking($user, $sender, $isResend);

            // Log the invitation activity
            $this->logInvitationActivity($user, $sender, $emailQueue->id, $isResend);

            DB::commit();

            return [
                'success' => true,
                'message' => $isResend ? 'Invitation resent successfully' : 'Invitation sent successfully',
                'email_queue_id' => $emailQueue->id,
                'expires_at' => $this->getInvitationExpiryDate(),
                'code' => 'INVITATION_SENT'
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to send user invitation', [
                'user_id' => $user->id,
                'sender_id' => $sender->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send invitation: ' . $e->getMessage(),
                'code' => 'SEND_FAILED'
            ];
        }
    }

    /**
     * Resend invitation to a user
     * 
     * @param User $user The user to resend invitation to
     * @param User $sender The administrator resending the invitation
     * @param string|null $customMessage Optional custom message
     * @return array Result with success status and details
     */
    public function resendInvitation(User $user, User $sender, ?string $customMessage = null): array
    {
        return $this->sendInvitation($user, $sender, $customMessage, true);
    }

    /**
     * Check if invitation has expired
     * 
     * @param User $user The user to check
     * @return bool True if invitation has expired
     */
    public function isInvitationExpired(User $user): bool
    {
        if ($user->status !== User::STATUS_PENDING) {
            return false;
        }

        $expiryDate = $user->created_at->addHours(self::INVITATION_EXPIRY_HOURS);
        return Carbon::now()->isAfter($expiryDate);
    }

    /**
     * Get invitation expiry date for a user
     * 
     * @param User|null $user The user (optional, uses current time if null)
     * @return Carbon The expiry date
     */
    public function getInvitationExpiryDate(?User $user = null): Carbon
    {
        $baseDate = $user ? $user->created_at : Carbon::now();
        return $baseDate->addHours(self::INVITATION_EXPIRY_HOURS);
    }

    /**
     * Get invitation statistics for a user
     * 
     * @param User $user The user to get stats for
     * @return array Invitation statistics
     */
    public function getInvitationStats(User $user): array
    {
        $emailsSent = EmailQueue::where('to_email', $user->email)
            ->where('email_type', 'invitation')
            ->count();

        $lastSent = EmailQueue::where('to_email', $user->email)
            ->where('email_type', 'invitation')
            ->latest('created_at')
            ->first();

        $activities = UserActivity::where('user_id', $user->id)
            ->where('action', 'invitation_sent')
            ->count();

        return [
            'total_invitations_sent' => $emailsSent,
            'total_resends' => max(0, $emailsSent - 1),
            'last_sent_at' => $lastSent?->created_at,
            'is_expired' => $this->isInvitationExpired($user),
            'expires_at' => $this->getInvitationExpiryDate($user),
            'can_resend' => $this->canResendInvitation($user),
            'activity_count' => $activities
        ];
    }

    /**
     * Check if invitation can be resent
     * 
     * @param User $user The user to check
     * @return bool True if invitation can be resent
     */
    public function canResendInvitation(User $user): bool
    {
        if ($user->status !== User::STATUS_PENDING) {
            return false;
        }

        $resendCount = EmailQueue::where('to_email', $user->email)
            ->where('email_type', 'invitation')
            ->count();

        return $resendCount < self::MAX_RESEND_ATTEMPTS;
    }

    /**
     * Validate if user is eligible for invitation
     * 
     * @param User $user The user to validate
     * @param bool $isResend Whether this is a resend attempt
     * @return array Validation result
     */
    private function validateInvitationEligibility(User $user, bool $isResend): array
    {
        // Check if user is in pending status
        if ($user->status !== User::STATUS_PENDING) {
            return [
                'eligible' => false,
                'reason' => 'User is not in pending status. Only pending users can receive invitations.'
            ];
        }

        // Check if user has already logged in
        if ($user->last_login_at) {
            return [
                'eligible' => false,
                'reason' => 'User has already logged in and activated their account.'
            ];
        }

        // For resends, check if maximum attempts reached
        if ($isResend && !$this->canResendInvitation($user)) {
            return [
                'eligible' => false,
                'reason' => 'Maximum resend attempts (' . self::MAX_RESEND_ATTEMPTS . ') reached for this user.'
            ];
        }

        // Check if invitation has expired
        if ($this->isInvitationExpired($user)) {
            return [
                'eligible' => false,
                'reason' => 'Invitation has expired. Please create a new user account.'
            ];
        }

        return [
            'eligible' => true,
            'reason' => null
        ];
    }

    /**
     * Get the invitation email template
     * 
     * @return EmailTemplate|null The invitation template
     */
    private function getInvitationTemplate(): ?EmailTemplate
    {
        return EmailTemplate::where('name', 'user_invitation')
            ->where('is_active', true)
            ->where('is_system_template', true)
            ->first();
    }

    /**
     * Get or generate temporary password for user
     * 
     * @param User $user The user
     * @return string The temporary password
     */
    private function getTemporaryPassword(User $user): string
    {
        // For security, we'll generate a new temporary password each time
        // The password is already stored in the user record during creation
        return $this->generateTemporaryPassword();
    }

    /**
     * Generate a temporary password
     * 
     * @return string 8-character temporary password
     */
    private function generateTemporaryPassword(): string
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*';

        // Ensure at least one character from each category
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];

        // Fill remaining 4 characters randomly
        $allChars = $uppercase . $lowercase . $numbers . $special;
        for ($i = 4; $i < 8; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }

        // Shuffle the password
        return str_shuffle($password);
    }

    /**
     * Prepare template data for invitation email
     * 
     * @param User $user The recipient user
     * @param User $sender The sender user
     * @param string $tempPassword The temporary password
     * @param string|null $customMessage Optional custom message
     * @return array Template data
     */
    private function prepareTemplateData(User $user, User $sender, string $tempPassword, ?string $customMessage): array
    {
        return [
            'user_name' => $user->full_name,
            'user_email' => $user->email,
            'temp_password' => $tempPassword,
            'login_url' => config('app.url') . '/login',
            'current_date' => Carbon::now()->format('F j, Y \a\t g:i A'),
            'company_name' => config('app.name', 'Analytics Hub'),
            'admin_name' => $sender->full_name,
            'admin_email' => $sender->email,
            'custom_message' => $customMessage
        ];
    }

    /**
     * Queue invitation email
     * 
     * @param User $user The recipient user
     * @param User $sender The sender user
     * @param EmailTemplate $template The email template
     * @param array $templateData The template data
     * @param bool $isResend Whether this is a resend
     * @return EmailQueue The created email queue entry
     */
    private function queueInvitationEmail(User $user, User $sender, EmailTemplate $template, array $templateData, bool $isResend): EmailQueue
    {
        return EmailQueue::create([
            'message_id' => Str::uuid(),
            'template_id' => $template->id,
            'subject' => $template->subject,
            'queue_name' => 'invitations',
            'to_email' => $user->email,
            'to_name' => $user->full_name,
            'from_email' => $template->from_email ?: config('mail.from.address'),
            'from_name' => $template->from_name ?: config('mail.from.name'),
            'html_body' => $this->renderTemplate($template->body_html, $templateData),
            'text_body' => $this->renderTemplate($template->body_text, $templateData),
            'template_data' => $templateData,
            'email_type' => 'invitation',
            'category' => 'authentication',
            'priority' => 'high',
            'language' => 'en',
            'scheduled_at' => Carbon::now(),
            'is_immediate' => true,
            'status' => 'pending',
            'attempts' => 0,
            'max_attempts' => 3,
            'retry_delay' => 300, // 5 minutes
            'user_id' => $user->id,
            'sender_user_id' => $sender->id,
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
            'user_context' => [
                'invitation_type' => $isResend ? 'resend' : 'initial',
                'user_status' => $user->status,
                'sender_role' => $sender->roles->pluck('name')->toArray()
            ],
            'metadata' => [
                'invitation_attempt' => $isResend ? 'resend' : 'initial',
                'expiry_date' => $this->getInvitationExpiryDate($user)->toISOString()
            ],
            'track_opens' => true,
            'track_clicks' => true,
            'created_by' => $sender->id
        ]);
    }

    /**
     * Update invitation tracking for user
     * 
     * @param User $user The user
     * @param User $sender The sender
     * @param bool $isResend Whether this is a resend
     * @return void
     */
    private function updateInvitationTracking(User $user, User $sender, bool $isResend): void
    {
        // Update user's invitation metadata
        $invitationData = $user->invitation_data ?? [];
        
        if (!$isResend) {
            $invitationData['initial_sent_at'] = Carbon::now()->toISOString();
            $invitationData['initial_sent_by'] = $sender->id;
        }
        
        $invitationData['last_sent_at'] = Carbon::now()->toISOString();
        $invitationData['last_sent_by'] = $sender->id;
        $invitationData['total_sent'] = ($invitationData['total_sent'] ?? 0) + 1;
        $invitationData['expires_at'] = $this->getInvitationExpiryDate($user)->toISOString();
        
        $user->update([
            'invitation_data' => $invitationData,
            'invitation_sent_at' => Carbon::now()
        ]);
    }

    /**
     * Log invitation activity
     * 
     * @param User $user The user
     * @param User $sender The sender
     * @param string $emailQueueId The email queue ID
     * @param bool $isResend Whether this is a resend
     * @return void
     */
    private function logInvitationActivity(User $user, User $sender, string $emailQueueId, bool $isResend): void
    {
        UserActivity::create([
            'user_id' => $user->id,
            'action' => $isResend ? 'invitation_resent' : 'invitation_sent',
            'description' => $isResend 
                ? "Invitation resent to {$user->email} by {$sender->full_name}"
                : "Invitation sent to {$user->email} by {$sender->full_name}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'performed_by' => $sender->id,
            'severity' => 'info',
            'category' => 'user_management',
            'context_data' => [
                'email_queue_id' => $emailQueueId,
                'recipient_email' => $user->email,
                'sender_id' => $sender->id,
                'invitation_type' => $isResend ? 'resend' : 'initial',
                'expires_at' => $this->getInvitationExpiryDate($user)->toISOString()
            ]
        ]);
    }

    /**
     * Render template with data
     * 
     * @param string $template The template content
     * @param array $data The template data
     * @return string The rendered template
     */
    private function renderTemplate(string $template, array $data): string
    {
        $rendered = $template;
        
        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $rendered = str_replace('{{' . $key . '}}', $value, $rendered);
            }
        }
        
        return $rendered;
    }

    /**
     * Get pending invitations that have expired
     * 
     * @return \Illuminate\Database\Eloquent\Collection Collection of expired users
     */
    public function getExpiredInvitations()
    {
        $expiryDate = Carbon::now()->subHours(self::INVITATION_EXPIRY_HOURS);
        
        return User::where('status', User::STATUS_PENDING)
            ->where('created_at', '<', $expiryDate)
            ->whereNull('last_login_at')
            ->get();
    }

    /**
     * Clean up expired invitations
     * 
     * @return array Cleanup results
     */
    public function cleanupExpiredInvitations(): array
    {
        $expiredUsers = $this->getExpiredInvitations();
        $cleanedCount = 0;
        $errors = [];

        foreach ($expiredUsers as $user) {
            try {
                DB::beginTransaction();
                
                // Soft delete the user
                $user->delete();
                
                // Log the cleanup activity
                UserActivity::create([
                    'user_id' => $user->id,
                    'action' => 'invitation_expired_cleanup',
                    'description' => "Expired invitation cleaned up for {$user->email}",
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'System Cleanup',
                    'session_id' => 'system',
                    'performed_by' => null,
                    'severity' => 'info',
                    'category' => 'system_maintenance',
                    'context_data' => [
                        'cleanup_reason' => 'invitation_expired',
                        'created_at' => $user->created_at->toISOString(),
                        'expired_days' => Carbon::now()->diffInDays($user->created_at)
                    ]
                ]);
                
                DB::commit();
                $cleanedCount++;
                
            } catch (Exception $e) {
                DB::rollBack();
                $errors[] = "Failed to cleanup user {$user->email}: " . $e->getMessage();
                Log::error('Failed to cleanup expired invitation', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'total_expired' => $expiredUsers->count(),
            'cleaned_count' => $cleanedCount,
            'errors' => $errors
        ];
    }
}