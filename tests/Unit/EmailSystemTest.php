<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\EmailTemplate;
use App\Models\EmailLog;
use App\Models\EmailQueue;
use App\Mail\WelcomeEmail;
use App\Mail\PasswordResetEmail;
use App\Mail\InvitationEmail;
use App\Services\EmailService;
use App\Services\EmailTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Class EmailSystemTest
 * 
 * Unit tests for email system functionality including email sending,
 * template management, delivery tracking, and queue processing.
 * 
 * @package Tests\Unit
 */
class EmailSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $testUser;
    protected EmailTemplate $welcomeTemplate;
    protected EmailTemplate $passwordResetTemplate;
    protected EmailService $emailService;
    protected EmailTemplateService $templateService;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setUpUser();
        $this->setUpEmailTemplates();
        $this->setUpServices();
        
        // Fake mail and queue for testing
        Mail::fake();
        Queue::fake();
    }

    /**
     * Set up test user
     */
    private function setUpUser(): void
    {
        $this->testUser = User::factory()->create([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'status' => 'active',
            'terms_accepted' => true
        ]);
    }

    /**
     * Set up email templates
     */
    private function setUpEmailTemplates(): void
    {
        $this->welcomeTemplate = EmailTemplate::create([
            'name' => 'welcome_email',
            'subject' => 'Welcome to {{app_name}}',
            'body' => '<h1>Welcome {{user_name}}!</h1><p>Thank you for joining {{app_name}}.</p>',
            'type' => 'system',
            'is_active' => true
        ]);
        
        $this->passwordResetTemplate = EmailTemplate::create([
            'name' => 'password_reset',
            'subject' => 'Password Reset Request',
            'body' => '<h1>Password Reset</h1><p>Click <a href="{{reset_url}}">here</a> to reset your password.</p>',
            'type' => 'system',
            'is_active' => true
        ]);
    }

    /**
     * Set up services
     */
    private function setUpServices(): void
    {
        $this->emailService = new EmailService();
        $this->templateService = new EmailTemplateService();
    }

    /**
     * Test email template creation
     */
    public function test_email_template_creation(): void
    {
        $templateData = [
            'name' => 'invitation_email',
            'subject' => 'You are invited to join {{app_name}}',
            'body' => '<h1>Invitation</h1><p>You have been invited to join {{app_name}}.</p>',
            'type' => 'system',
            'is_active' => true
        ];
        
        $template = EmailTemplate::create($templateData);
        
        $this->assertInstanceOf(EmailTemplate::class, $template);
        $this->assertEquals('invitation_email', $template->name);
        $this->assertEquals('You are invited to join {{app_name}}', $template->subject);
        $this->assertTrue($template->is_active);
        $this->assertTrue(Str::isUuid($template->id));
    }

    /**
     * Test email template variable replacement
     */
    public function test_email_template_variable_replacement(): void
    {
        $variables = [
            'app_name' => 'Analytics Hub',
            'user_name' => 'John Doe'
        ];
        
        $processedSubject = $this->templateService->replaceVariables(
            $this->welcomeTemplate->subject,
            $variables
        );
        
        $processedBody = $this->templateService->replaceVariables(
            $this->welcomeTemplate->body,
            $variables
        );
        
        $this->assertEquals('Welcome to Analytics Hub', $processedSubject);
        $this->assertStringContains('Welcome John Doe!', $processedBody);
        $this->assertStringContains('Analytics Hub', $processedBody);
    }

    /**
     * Test welcome email sending
     */
    public function test_welcome_email_sending(): void
    {
        $this->emailService->sendWelcomeEmail($this->testUser);
        
        // Assert email was queued
        Mail::assertQueued(WelcomeEmail::class, function ($mail) {
            return $mail->hasTo($this->testUser->email);
        });
        
        // Check email log entry
        $emailLog = EmailLog::where('recipient_email', $this->testUser->email)
            ->where('email_type', 'welcome')
            ->first();
            
        $this->assertNotNull($emailLog);
        $this->assertEquals('queued', $emailLog->status);
    }

    /**
     * Test password reset email sending
     */
    public function test_password_reset_email_sending(): void
    {
        $resetToken = Str::random(60);
        
        $this->emailService->sendPasswordResetEmail($this->testUser, $resetToken);
        
        // Assert email was queued
        Mail::assertQueued(PasswordResetEmail::class, function ($mail) {
            return $mail->hasTo($this->testUser->email);
        });
        
        // Check email log entry
        $emailLog = EmailLog::where('recipient_email', $this->testUser->email)
            ->where('email_type', 'password_reset')
            ->first();
            
        $this->assertNotNull($emailLog);
        $this->assertEquals('queued', $emailLog->status);
    }

    /**
     * Test invitation email sending
     */
    public function test_invitation_email_sending(): void
    {
        $invitationData = [
            'email' => 'invited@example.com',
            'name' => 'Invited User',
            'invitation_token' => Str::random(60)
        ];
        
        $this->emailService->sendInvitationEmail($invitationData);
        
        // Assert email was queued
        Mail::assertQueued(InvitationEmail::class, function ($mail) use ($invitationData) {
            return $mail->hasTo($invitationData['email']);
        });
        
        // Check email log entry
        $emailLog = EmailLog::where('recipient_email', $invitationData['email'])
            ->where('email_type', 'invitation')
            ->first();
            
        $this->assertNotNull($emailLog);
        $this->assertEquals('queued', $emailLog->status);
    }

    /**
     * Test email queue processing
     */
    public function test_email_queue_processing(): void
    {
        // Create email queue entry
        $queueEntry = EmailQueue::create([
            'recipient_email' => $this->testUser->email,
            'recipient_name' => $this->testUser->name,
            'subject' => 'Test Email',
            'body' => '<p>This is a test email.</p>',
            'email_type' => 'test',
            'status' => 'pending',
            'scheduled_at' => now()
        ]);
        
        // Process queue entry
        $this->emailService->processQueueEntry($queueEntry);
        
        // Check status updated
        $this->assertEquals('sent', $queueEntry->fresh()->status);
        $this->assertNotNull($queueEntry->fresh()->sent_at);
    }

    /**
     * Test email delivery tracking
     */
    public function test_email_delivery_tracking(): void
    {
        $emailLog = EmailLog::create([
            'recipient_email' => $this->testUser->email,
            'recipient_name' => $this->testUser->name,
            'subject' => 'Test Email',
            'email_type' => 'test',
            'status' => 'sent',
            'sent_at' => now()
        ]);
        
        // Simulate delivery confirmation
        $this->emailService->markAsDelivered($emailLog->id);
        
        $this->assertEquals('delivered', $emailLog->fresh()->status);
        $this->assertNotNull($emailLog->fresh()->delivered_at);
    }

    /**
     * Test email bounce handling
     */
    public function test_email_bounce_handling(): void
    {
        $emailLog = EmailLog::create([
            'recipient_email' => 'bounce@example.com',
            'recipient_name' => 'Bounce User',
            'subject' => 'Test Email',
            'email_type' => 'test',
            'status' => 'sent',
            'sent_at' => now()
        ]);
        
        $bounceReason = 'Mailbox does not exist';
        
        // Simulate bounce
        $this->emailService->markAsBounced($emailLog->id, $bounceReason);
        
        $this->assertEquals('bounced', $emailLog->fresh()->status);
        $this->assertEquals($bounceReason, $emailLog->fresh()->bounce_reason);
        $this->assertNotNull($emailLog->fresh()->bounced_at);
    }

    /**
     * Test email template validation
     */
    public function test_email_template_validation(): void
    {
        // Test required fields
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        EmailTemplate::create([
            'subject' => 'Test Subject',
            'body' => 'Test Body'
            // Missing required 'name' field
        ]);
    }

    /**
     * Test email template status management
     */
    public function test_email_template_status_management(): void
    {
        // Test active template
        $this->assertTrue($this->welcomeTemplate->is_active);
        
        // Deactivate template
        $this->welcomeTemplate->update(['is_active' => false]);
        $this->assertFalse($this->welcomeTemplate->fresh()->is_active);
        
        // Reactivate template
        $this->welcomeTemplate->update(['is_active' => true]);
        $this->assertTrue($this->welcomeTemplate->fresh()->is_active);
    }

    /**
     * Test email template search
     */
    public function test_email_template_search(): void
    {
        // Create additional templates
        EmailTemplate::create([
            'name' => 'notification_email',
            'subject' => 'New Notification',
            'body' => '<p>You have a new notification.</p>',
            'type' => 'notification',
            'is_active' => true
        ]);
        
        // Search by name
        $results = EmailTemplate::where('name', 'LIKE', '%welcome%')->get();
        $this->assertEquals(1, $results->count());
        $this->assertEquals('welcome_email', $results->first()->name);
        
        // Search by type
        $systemTemplates = EmailTemplate::where('type', 'system')->get();
        $this->assertGreaterThanOrEqual(2, $systemTemplates->count());
    }

    /**
     * Test email log filtering
     */
    public function test_email_log_filtering(): void
    {
        // Create email logs with different statuses
        EmailLog::create([
            'recipient_email' => 'sent@example.com',
            'subject' => 'Sent Email',
            'email_type' => 'test',
            'status' => 'sent',
            'sent_at' => now()
        ]);
        
        EmailLog::create([
            'recipient_email' => 'failed@example.com',
            'subject' => 'Failed Email',
            'email_type' => 'test',
            'status' => 'failed',
            'failed_at' => now()
        ]);
        
        // Filter by status
        $sentEmails = EmailLog::where('status', 'sent')->get();
        $this->assertGreaterThanOrEqual(1, $sentEmails->count());
        
        $failedEmails = EmailLog::where('status', 'failed')->get();
        $this->assertEquals(1, $failedEmails->count());
    }

    /**
     * Test email statistics
     */
    public function test_email_statistics(): void
    {
        // Create various email logs
        $statuses = ['sent', 'delivered', 'bounced', 'failed'];
        
        foreach ($statuses as $status) {
            EmailLog::create([
                'recipient_email' => $status . '@example.com',
                'subject' => ucfirst($status) . ' Email',
                'email_type' => 'test',
                'status' => $status,
                $status . '_at' => now()
            ]);
        }
        
        $stats = $this->emailService->getEmailStatistics();
        
        $this->assertArrayHasKey('total_sent', $stats);
        $this->assertArrayHasKey('total_delivered', $stats);
        $this->assertArrayHasKey('total_bounced', $stats);
        $this->assertArrayHasKey('total_failed', $stats);
        $this->assertArrayHasKey('delivery_rate', $stats);
        $this->assertArrayHasKey('bounce_rate', $stats);
    }

    /**
     * Test email retry mechanism
     */
    public function test_email_retry_mechanism(): void
    {
        $emailLog = EmailLog::create([
            'recipient_email' => $this->testUser->email,
            'subject' => 'Retry Test Email',
            'email_type' => 'test',
            'status' => 'failed',
            'retry_count' => 0,
            'failed_at' => now()
        ]);
        
        // Retry email
        $this->emailService->retryEmail($emailLog->id);
        
        $this->assertEquals(1, $emailLog->fresh()->retry_count);
        $this->assertEquals('queued', $emailLog->fresh()->status);
    }

    /**
     * Test email rate limiting
     */
    public function test_email_rate_limiting(): void
    {
        $rateLimitConfig = [
            'max_emails_per_hour' => 100,
            'max_emails_per_day' => 1000
        ];
        
        // Test rate limit check
        $canSend = $this->emailService->checkRateLimit(
            $this->testUser->email,
            $rateLimitConfig
        );
        
        $this->assertTrue($canSend);
        
        // Simulate reaching rate limit
        for ($i = 0; $i < 101; $i++) {
            EmailLog::create([
                'recipient_email' => $this->testUser->email,
                'subject' => 'Rate Limit Test ' . $i,
                'email_type' => 'test',
                'status' => 'sent',
                'sent_at' => now()
            ]);
        }
        
        $canSendAfterLimit = $this->emailService->checkRateLimit(
            $this->testUser->email,
            $rateLimitConfig
        );
        
        $this->assertFalse($canSendAfterLimit);
    }

    /**
     * Test email template versioning
     */
    public function test_email_template_versioning(): void
    {
        $originalBody = $this->welcomeTemplate->body;
        $newBody = '<h1>Updated Welcome {{user_name}}!</h1><p>Welcome to the new {{app_name}}.</p>';
        
        // Update template and create version
        $this->templateService->updateTemplate($this->welcomeTemplate->id, [
            'body' => $newBody
        ]);
        
        $this->assertEquals($newBody, $this->welcomeTemplate->fresh()->body);
        
        // Check if version was created
        $version = $this->welcomeTemplate->versions()->latest()->first();
        $this->assertNotNull($version);
        $this->assertEquals($originalBody, $version->body);
    }

    /**
     * Test email blacklist functionality
     */
    public function test_email_blacklist_functionality(): void
    {
        $blacklistedEmail = 'blacklisted@example.com';
        
        // Add email to blacklist
        $this->emailService->addToBlacklist($blacklistedEmail, 'User requested removal');
        
        // Test if email is blacklisted
        $isBlacklisted = $this->emailService->isBlacklisted($blacklistedEmail);
        $this->assertTrue($isBlacklisted);
        
        // Try to send email to blacklisted address
        $canSend = $this->emailService->canSendToEmail($blacklistedEmail);
        $this->assertFalse($canSend);
    }

    /**
     * Test email unsubscribe functionality
     */
    public function test_email_unsubscribe_functionality(): void
    {
        $unsubscribeToken = $this->emailService->generateUnsubscribeToken($this->testUser->email);
        
        $this->assertNotEmpty($unsubscribeToken);
        
        // Process unsubscribe
        $this->emailService->processUnsubscribe($unsubscribeToken);
        
        // Check if user is unsubscribed
        $isUnsubscribed = $this->emailService->isUnsubscribed($this->testUser->email);
        $this->assertTrue($isUnsubscribed);
    }

    /**
     * Test email attachment handling
     */
    public function test_email_attachment_handling(): void
    {
        $attachmentData = [
            'filename' => 'report.pdf',
            'content' => base64_encode('PDF content'),
            'mime_type' => 'application/pdf'
        ];
        
        $emailData = [
            'recipient_email' => $this->testUser->email,
            'subject' => 'Email with Attachment',
            'body' => '<p>Please find the attached report.</p>',
            'attachments' => [$attachmentData]
        ];
        
        $this->emailService->sendEmailWithAttachments($emailData);
        
        // Check email log includes attachment info
        $emailLog = EmailLog::where('recipient_email', $this->testUser->email)
            ->where('subject', 'Email with Attachment')
            ->first();
            
        $this->assertNotNull($emailLog);
        $this->assertTrue($emailLog->has_attachments);
        $this->assertEquals(1, $emailLog->attachment_count);
    }

    /**
     * Test email scheduling
     */
    public function test_email_scheduling(): void
    {
        $scheduledTime = now()->addHours(2);
        
        $this->emailService->scheduleEmail([
            'recipient_email' => $this->testUser->email,
            'subject' => 'Scheduled Email',
            'body' => '<p>This email was scheduled.</p>',
            'email_type' => 'scheduled',
            'scheduled_at' => $scheduledTime
        ]);
        
        // Check queue entry
        $queueEntry = EmailQueue::where('recipient_email', $this->testUser->email)
            ->where('status', 'scheduled')
            ->first();
            
        $this->assertNotNull($queueEntry);
        $this->assertEquals($scheduledTime->format('Y-m-d H:i:s'), $queueEntry->scheduled_at->format('Y-m-d H:i:s'));
    }

    /**
     * Test email template preview
     */
    public function test_email_template_preview(): void
    {
        $variables = [
            'app_name' => 'Analytics Hub',
            'user_name' => 'John Doe'
        ];
        
        $preview = $this->templateService->previewTemplate(
            $this->welcomeTemplate->id,
            $variables
        );
        
        $this->assertArrayHasKey('subject', $preview);
        $this->assertArrayHasKey('body', $preview);
        $this->assertEquals('Welcome to Analytics Hub', $preview['subject']);
        $this->assertStringContains('Welcome John Doe!', $preview['body']);
    }

    /**
     * Test email delivery reports
     */
    public function test_email_delivery_reports(): void
    {
        $startDate = now()->subDays(7);
        $endDate = now();
        
        $report = $this->emailService->generateDeliveryReport($startDate, $endDate);
        
        $this->assertArrayHasKey('total_emails', $report);
        $this->assertArrayHasKey('sent_emails', $report);
        $this->assertArrayHasKey('delivered_emails', $report);
        $this->assertArrayHasKey('bounced_emails', $report);
        $this->assertArrayHasKey('failed_emails', $report);
        $this->assertArrayHasKey('delivery_rate', $report);
        $this->assertArrayHasKey('bounce_rate', $report);
    }

    /**
     * Clean up after tests
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }
}