<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Models\NotificationSetting;
use App\Services\NotificationService;
use App\Notifications\SystemNotification;
use App\Notifications\WelcomeNotification;
use App\Notifications\PasswordChangeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Class NotificationSystemTest
 * 
 * Unit tests for notification system functionality including notification
 * creation, delivery, templates, and user preferences.
 * 
 * @package Tests\Unit
 */
class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $testUser;
    protected User $adminUser;
    protected NotificationTemplate $systemTemplate;
    protected NotificationTemplate $welcomeTemplate;
    protected NotificationService $notificationService;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setUpUsers();
        $this->setUpNotificationTemplates();
        $this->setUpNotificationSettings();
        $this->setUpServices();
        
        // Fake notifications for testing
        NotificationFacade::fake();
    }

    /**
     * Set up test users
     */
    private function setUpUsers(): void
    {
        $this->testUser = User::factory()->create([
            'email' => 'user@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'status' => 'active',
            'terms_accepted' => true
        ]);
        
        $this->adminUser = User::factory()->create([
            'email' => 'admin@example.com',
            'first_name' => 'Admin',
            'last_name' => 'User',
            'status' => 'active',
            'terms_accepted' => true
        ]);
    }

    /**
     * Set up notification templates
     */
    private function setUpNotificationTemplates(): void
    {
        $this->systemTemplate = NotificationTemplate::create([
            'name' => 'system_notification',
            'title' => 'System Notification',
            'message' => 'System message: {{message}}',
            'type' => 'system',
            'channels' => ['database', 'mail'],
            'is_active' => true
        ]);
        
        $this->welcomeTemplate = NotificationTemplate::create([
            'name' => 'welcome_notification',
            'title' => 'Welcome to {{app_name}}',
            'message' => 'Welcome {{user_name}}! Thank you for joining {{app_name}}.',
            'type' => 'welcome',
            'channels' => ['database', 'mail'],
            'is_active' => true
        ]);
    }

    /**
     * Set up notification settings
     */
    private function setUpNotificationSettings(): void
    {
        NotificationSetting::create([
            'user_id' => $this->testUser->id,
            'notification_type' => 'system',
            'email_enabled' => true,
            'database_enabled' => true,
            'push_enabled' => false
        ]);
        
        NotificationSetting::create([
            'user_id' => $this->testUser->id,
            'notification_type' => 'welcome',
            'email_enabled' => true,
            'database_enabled' => true,
            'push_enabled' => false
        ]);
    }

    /**
     * Set up services
     */
    private function setUpServices(): void
    {
        $this->notificationService = new NotificationService();
    }

    /**
     * Test notification template creation
     */
    public function test_notification_template_creation(): void
    {
        $templateData = [
            'name' => 'password_reset_notification',
            'title' => 'Password Reset Request',
            'message' => 'A password reset was requested for your account.',
            'type' => 'security',
            'channels' => ['database', 'mail', 'sms'],
            'is_active' => true
        ];
        
        $template = NotificationTemplate::create($templateData);
        
        $this->assertInstanceOf(NotificationTemplate::class, $template);
        $this->assertEquals('password_reset_notification', $template->name);
        $this->assertEquals('Password Reset Request', $template->title);
        $this->assertEquals('security', $template->type);
        $this->assertTrue($template->is_active);
        $this->assertTrue(Str::isUuid($template->id));
    }

    /**
     * Test notification creation
     */
    public function test_notification_creation(): void
    {
        $notificationData = [
            'user_id' => $this->testUser->id,
            'title' => 'Test Notification',
            'message' => 'This is a test notification.',
            'type' => 'info',
            'data' => json_encode(['key' => 'value']),
            'read_at' => null
        ];
        
        $notification = Notification::create($notificationData);
        
        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals($this->testUser->id, $notification->user_id);
        $this->assertEquals('Test Notification', $notification->title);
        $this->assertEquals('info', $notification->type);
        $this->assertNull($notification->read_at);
        $this->assertTrue(Str::isUuid($notification->id));
    }

    /**
     * Test system notification sending
     */
    public function test_system_notification_sending(): void
    {
        $message = 'System maintenance scheduled for tonight.';
        
        $this->notificationService->sendSystemNotification(
            $this->testUser,
            $message
        );
        
        // Assert notification was sent
        NotificationFacade::assertSentTo(
            $this->testUser,
            SystemNotification::class,
            function ($notification) use ($message) {
                return $notification->getMessage() === $message;
            }
        );
        
        // Check database notification
        $dbNotification = Notification::where('user_id', $this->testUser->id)
            ->where('type', 'system')
            ->first();
            
        $this->assertNotNull($dbNotification);
        $this->assertStringContains($message, $dbNotification->message);
    }

    /**
     * Test welcome notification sending
     */
    public function test_welcome_notification_sending(): void
    {
        $this->notificationService->sendWelcomeNotification($this->testUser);
        
        // Assert notification was sent
        NotificationFacade::assertSentTo(
            $this->testUser,
            WelcomeNotification::class
        );
        
        // Check database notification
        $dbNotification = Notification::where('user_id', $this->testUser->id)
            ->where('type', 'welcome')
            ->first();
            
        $this->assertNotNull($dbNotification);
        $this->assertStringContains('Welcome', $dbNotification->title);
    }

    /**
     * Test notification template variable replacement
     */
    public function test_notification_template_variable_replacement(): void
    {
        $variables = [
            'app_name' => 'Analytics Hub',
            'user_name' => 'John Doe'
        ];
        
        $processedTitle = $this->notificationService->replaceVariables(
            $this->welcomeTemplate->title,
            $variables
        );
        
        $processedMessage = $this->notificationService->replaceVariables(
            $this->welcomeTemplate->message,
            $variables
        );
        
        $this->assertEquals('Welcome to Analytics Hub', $processedTitle);
        $this->assertStringContains('Welcome John Doe!', $processedMessage);
        $this->assertStringContains('Analytics Hub', $processedMessage);
    }

    /**
     * Test notification reading
     */
    public function test_notification_reading(): void
    {
        $notification = Notification::create([
            'user_id' => $this->testUser->id,
            'title' => 'Unread Notification',
            'message' => 'This notification is unread.',
            'type' => 'info'
        ]);
        
        // Initially unread
        $this->assertNull($notification->read_at);
        $this->assertFalse($notification->isRead());
        
        // Mark as read
        $this->notificationService->markAsRead($notification->id);
        
        $notification->refresh();
        $this->assertNotNull($notification->read_at);
        $this->assertTrue($notification->isRead());
    }

    /**
     * Test bulk notification reading
     */
    public function test_bulk_notification_reading(): void
    {
        // Create multiple unread notifications
        $notifications = [];
        for ($i = 0; $i < 3; $i++) {
            $notifications[] = Notification::create([
                'user_id' => $this->testUser->id,
                'title' => 'Notification ' . ($i + 1),
                'message' => 'Message ' . ($i + 1),
                'type' => 'info'
            ]);
        }
        
        // Mark all as read
        $this->notificationService->markAllAsRead($this->testUser->id);
        
        // Check all are read
        foreach ($notifications as $notification) {
            $notification->refresh();
            $this->assertTrue($notification->isRead());
        }
    }

    /**
     * Test notification deletion
     */
    public function test_notification_deletion(): void
    {
        $notification = Notification::create([
            'user_id' => $this->testUser->id,
            'title' => 'To Be Deleted',
            'message' => 'This notification will be deleted.',
            'type' => 'info'
        ]);
        
        $notificationId = $notification->id;
        
        // Delete notification
        $this->notificationService->deleteNotification($notificationId);
        
        // Check notification is deleted
        $this->assertNull(Notification::find($notificationId));
    }

    /**
     * Test notification settings management
     */
    public function test_notification_settings_management(): void
    {
        $settings = [
            'system' => [
                'email_enabled' => false,
                'database_enabled' => true,
                'push_enabled' => false
            ],
            'welcome' => [
                'email_enabled' => true,
                'database_enabled' => true,
                'push_enabled' => true
            ]
        ];
        
        $this->notificationService->updateUserSettings($this->testUser->id, $settings);
        
        // Check settings were updated
        $systemSetting = NotificationSetting::where('user_id', $this->testUser->id)
            ->where('notification_type', 'system')
            ->first();
            
        $this->assertFalse($systemSetting->email_enabled);
        $this->assertTrue($systemSetting->database_enabled);
        $this->assertFalse($systemSetting->push_enabled);
        
        $welcomeSetting = NotificationSetting::where('user_id', $this->testUser->id)
            ->where('notification_type', 'welcome')
            ->first();
            
        $this->assertTrue($welcomeSetting->email_enabled);
        $this->assertTrue($welcomeSetting->database_enabled);
        $this->assertTrue($welcomeSetting->push_enabled);
    }

    /**
     * Test notification channel filtering
     */
    public function test_notification_channel_filtering(): void
    {
        // User has email disabled for system notifications
        NotificationSetting::where('user_id', $this->testUser->id)
            ->where('notification_type', 'system')
            ->update(['email_enabled' => false]);
        
        $enabledChannels = $this->notificationService->getEnabledChannels(
            $this->testUser->id,
            'system'
        );
        
        $this->assertContains('database', $enabledChannels);
        $this->assertNotContains('mail', $enabledChannels);
    }

    /**
     * Test notification statistics
     */
    public function test_notification_statistics(): void
    {
        // Create notifications with different statuses
        Notification::create([
            'user_id' => $this->testUser->id,
            'title' => 'Read Notification',
            'message' => 'This is read.',
            'type' => 'info',
            'read_at' => now()
        ]);
        
        Notification::create([
            'user_id' => $this->testUser->id,
            'title' => 'Unread Notification',
            'message' => 'This is unread.',
            'type' => 'info'
        ]);
        
        $stats = $this->notificationService->getUserStatistics($this->testUser->id);
        
        $this->assertArrayHasKey('total_notifications', $stats);
        $this->assertArrayHasKey('unread_notifications', $stats);
        $this->assertArrayHasKey('read_notifications', $stats);
        $this->assertGreaterThanOrEqual(2, $stats['total_notifications']);
        $this->assertGreaterThanOrEqual(1, $stats['unread_notifications']);
    }

    /**
     * Test notification filtering by type
     */
    public function test_notification_filtering_by_type(): void
    {
        // Create notifications of different types
        Notification::create([
            'user_id' => $this->testUser->id,
            'title' => 'System Notification',
            'message' => 'System message.',
            'type' => 'system'
        ]);
        
        Notification::create([
            'user_id' => $this->testUser->id,
            'title' => 'Info Notification',
            'message' => 'Info message.',
            'type' => 'info'
        ]);
        
        Notification::create([
            'user_id' => $this->testUser->id,
            'title' => 'Warning Notification',
            'message' => 'Warning message.',
            'type' => 'warning'
        ]);
        
        // Filter by type
        $systemNotifications = $this->notificationService->getUserNotificationsByType(
            $this->testUser->id,
            'system'
        );
        
        $this->assertGreaterThanOrEqual(1, $systemNotifications->count());
        
        foreach ($systemNotifications as $notification) {
            $this->assertEquals('system', $notification->type);
        }
    }

    /**
     * Test notification pagination
     */
    public function test_notification_pagination(): void
    {
        // Create multiple notifications
        for ($i = 0; $i < 15; $i++) {
            Notification::create([
                'user_id' => $this->testUser->id,
                'title' => 'Notification ' . ($i + 1),
                'message' => 'Message ' . ($i + 1),
                'type' => 'info'
            ]);
        }
        
        $paginatedNotifications = $this->notificationService->getUserNotificationsPaginated(
            $this->testUser->id,
            10 // per page
        );
        
        $this->assertEquals(10, $paginatedNotifications->count());
        $this->assertGreaterThanOrEqual(15, $paginatedNotifications->total());
    }

    /**
     * Test notification search
     */
    public function test_notification_search(): void
    {
        // Create searchable notifications
        Notification::create([
            'user_id' => $this->testUser->id,
            'title' => 'Important System Update',
            'message' => 'The system will be updated tonight.',
            'type' => 'system'
        ]);
        
        Notification::create([
            'user_id' => $this->testUser->id,
            'title' => 'Welcome Message',
            'message' => 'Welcome to our platform!',
            'type' => 'welcome'
        ]);
        
        // Search notifications
        $searchResults = $this->notificationService->searchUserNotifications(
            $this->testUser->id,
            'system'
        );
        
        $this->assertGreaterThanOrEqual(1, $searchResults->count());
        
        $found = false;
        foreach ($searchResults as $notification) {
            if (str_contains($notification->title, 'System') || 
                str_contains($notification->message, 'system')) {
                $found = true;
                break;
            }
        }
        
        $this->assertTrue($found);
    }

    /**
     * Test notification template status management
     */
    public function test_notification_template_status_management(): void
    {
        // Test active template
        $this->assertTrue($this->systemTemplate->is_active);
        
        // Deactivate template
        $this->systemTemplate->update(['is_active' => false]);
        $this->assertFalse($this->systemTemplate->fresh()->is_active);
        
        // Reactivate template
        $this->systemTemplate->update(['is_active' => true]);
        $this->assertTrue($this->systemTemplate->fresh()->is_active);
    }

    /**
     * Test notification priority handling
     */
    public function test_notification_priority_handling(): void
    {
        $highPriorityNotification = Notification::create([
            'user_id' => $this->testUser->id,
            'title' => 'High Priority Alert',
            'message' => 'This is urgent!',
            'type' => 'alert',
            'priority' => 'high'
        ]);
        
        $normalPriorityNotification = Notification::create([
            'user_id' => $this->testUser->id,
            'title' => 'Normal Notification',
            'message' => 'This is normal.',
            'type' => 'info',
            'priority' => 'normal'
        ]);
        
        // Get notifications ordered by priority
        $prioritizedNotifications = $this->notificationService->getUserNotificationsByPriority(
            $this->testUser->id
        );
        
        // High priority should come first
        $this->assertEquals('high', $prioritizedNotifications->first()->priority);
    }

    /**
     * Test notification expiry
     */
    public function test_notification_expiry(): void
    {
        $expiringNotification = Notification::create([
            'user_id' => $this->testUser->id,
            'title' => 'Expiring Notification',
            'message' => 'This notification will expire.',
            'type' => 'info',
            'expires_at' => now()->addDays(1)
        ]);
        
        // Notification should not be expired yet
        $this->assertFalse($expiringNotification->isExpired());
        
        // Set expiry to past
        $expiringNotification->update(['expires_at' => now()->subDays(1)]);
        
        // Notification should now be expired
        $this->assertTrue($expiringNotification->fresh()->isExpired());
    }

    /**
     * Test notification cleanup
     */
    public function test_notification_cleanup(): void
    {
        // Create old read notifications
        for ($i = 0; $i < 5; $i++) {
            Notification::create([
                'user_id' => $this->testUser->id,
                'title' => 'Old Notification ' . ($i + 1),
                'message' => 'Old message.',
                'type' => 'info',
                'read_at' => now()->subDays(35), // Older than 30 days
                'created_at' => now()->subDays(35)
            ]);
        }
        
        $initialCount = Notification::where('user_id', $this->testUser->id)->count();
        
        // Clean up old notifications
        $this->notificationService->cleanupOldNotifications(30); // Keep 30 days
        
        $finalCount = Notification::where('user_id', $this->testUser->id)->count();
        
        $this->assertLessThan($initialCount, $finalCount);
    }

    /**
     * Test notification delivery preferences
     */
    public function test_notification_delivery_preferences(): void
    {
        $preferences = [
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '08:00',
            'timezone' => 'UTC',
            'digest_frequency' => 'daily'
        ];
        
        $this->notificationService->updateDeliveryPreferences(
            $this->testUser->id,
            $preferences
        );
        
        $savedPreferences = $this->notificationService->getDeliveryPreferences(
            $this->testUser->id
        );
        
        $this->assertEquals('22:00', $savedPreferences['quiet_hours_start']);
        $this->assertEquals('08:00', $savedPreferences['quiet_hours_end']);
        $this->assertEquals('daily', $savedPreferences['digest_frequency']);
    }

    /**
     * Test notification digest generation
     */
    public function test_notification_digest_generation(): void
    {
        // Create multiple unread notifications
        for ($i = 0; $i < 5; $i++) {
            Notification::create([
                'user_id' => $this->testUser->id,
                'title' => 'Digest Notification ' . ($i + 1),
                'message' => 'Message for digest.',
                'type' => 'info',
                'created_at' => now()->subHours($i)
            ]);
        }
        
        $digest = $this->notificationService->generateDigest(
            $this->testUser->id,
            'daily'
        );
        
        $this->assertArrayHasKey('user', $digest);
        $this->assertArrayHasKey('notifications', $digest);
        $this->assertArrayHasKey('summary', $digest);
        $this->assertGreaterThanOrEqual(5, count($digest['notifications']));
    }

    /**
     * Test notification template validation
     */
    public function test_notification_template_validation(): void
    {
        // Test required fields
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        NotificationTemplate::create([
            'title' => 'Test Title',
            'message' => 'Test Message'
            // Missing required 'name' field
        ]);
    }

    /**
     * Clean up after tests
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }
}