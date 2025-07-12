<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notification;
use App\Models\User;
use App\Models\Role;
use Carbon\Carbon;

/**
 * Class NotificationTestSeeder
 * 
 * Seeds test notifications for development and testing purposes.
 * Creates various types of notifications with different priorities,
 * statuses, and targeting options to test the notification system.
 * 
 * @package Database\Seeders
 * @author Analytics Hub Team
 * @version 1.0.0
 */
class NotificationTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates test notifications with various configurations:
     * - Different types (system, announcement, alert, reminder)
     * - Different priorities (high, medium, low)
     * - Different statuses (draft, scheduled, sent)
     * - Different targeting (all users, specific users, role-based)
     * 
     * @return void
     */
    public function run(): void
    {
        // Get users and roles for targeting
        $users = User::limit(5)->get();
        $adminRole = Role::where('name', 'admin')->first();
        $userRole = Role::where('name', 'user')->first();
        
        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please run UserSeeder first.');
            return;
        }
        
        // Test notifications data
        $notifications = [
            [
                'title' => 'Welcome to Analytics Hub',
                'type' => 'announcement',
                'priority' => 'high',
                'category' => 'welcome',
                'message' => 'Welcome to Analytics Hub! We\'re excited to have you on board. Explore our powerful analytics tools and discover insights from your data.',
                'action_url' => route('dashboard'),
                'action_text' => 'Get Started',
                'target_type' => 'all_users',
                'status' => 'sent',
                'sent_at' => Carbon::now()->subHours(2),
            ],
            [
                'title' => 'System Maintenance Scheduled',
                'type' => 'system',
                'priority' => 'medium',
                'category' => 'maintenance',
                'message' => 'We have scheduled system maintenance for tonight from 2:00 AM to 4:00 AM. During this time, some features may be temporarily unavailable.',
                'target_type' => 'all_users',
                'status' => 'scheduled',
                'scheduled_at' => Carbon::now()->addHours(6),
            ],
            [
                'title' => 'Security Alert: New Login Detected',
                'type' => 'alert',
                'priority' => 'high',
                'category' => 'security',
                'message' => 'We detected a new login to your account from a different device or location. If this wasn\'t you, please secure your account immediately.',
                'action_url' => '#',
                'action_text' => 'Review Activity',
                'target_type' => 'specific_users',
                'target_user_ids' => $users->take(2)->pluck('id')->toArray(),
                'status' => 'sent',
                'sent_at' => Carbon::now()->subMinutes(30),
            ],
            [
                'title' => 'Monthly Report Available',
                'type' => 'reminder',
                'priority' => 'low',
                'category' => 'reports',
                'message' => 'Your monthly analytics report is now available for download. Review your key metrics and performance indicators.',
                'action_url' => '#',
                'action_text' => 'Download Report',
                'target_type' => 'role_based',
                'target_role_ids' => $adminRole ? [$adminRole->id] : [],
                'status' => 'sent',
                'sent_at' => Carbon::now()->subDays(1),
            ],
            [
                'title' => 'New Feature: Advanced Analytics',
                'type' => 'announcement',
                'priority' => 'medium',
                'category' => 'features',
                'message' => 'We\'ve just released our new Advanced Analytics module! Dive deeper into your data with enhanced visualization tools and predictive insights.',
                'action_url' => '#',
                'action_text' => 'Explore Now',
                'target_type' => 'all_users',
                'status' => 'sent',
                'sent_at' => Carbon::now()->subHours(12),
            ],
            [
                'title' => 'Profile Completion Reminder',
                'type' => 'reminder',
                'priority' => 'low',
                'category' => 'profile',
                'message' => 'Complete your profile to get the most out of Analytics Hub. Add your preferences and customize your dashboard.',
                'action_url' => '#',
                'action_text' => 'Complete Profile',
                'target_type' => 'specific_users',
                'target_user_ids' => $users->skip(2)->take(2)->pluck('id')->toArray(),
                'status' => 'draft',
            ],
            [
                'title' => 'Weekly Team Meeting',
                'type' => 'reminder',
                'priority' => 'medium',
                'category' => 'meetings',
                'message' => 'Don\'t forget about our weekly team meeting tomorrow at 10:00 AM. We\'ll be discussing project updates and new initiatives.',
                'target_type' => 'role_based',
                'target_role_ids' => $userRole ? [$userRole->id] : [],
                'status' => 'scheduled',
                'scheduled_at' => Carbon::now()->addDay(),
                'expires_at' => Carbon::now()->addDays(2),
            ],
            [
                'title' => 'Data Backup Completed',
                'type' => 'system',
                'priority' => 'low',
                'category' => 'backup',
                'message' => 'Your data backup has been completed successfully. All your information is safely stored and protected.',
                'target_type' => 'all_users',
                'status' => 'sent',
                'sent_at' => Carbon::now()->subHours(6),
            ],
        ];
        
        // Create notifications
        foreach ($notifications as $notificationData) {
            $notification = Notification::create($notificationData);
            
            // If notification is sent, create user notification records
            if ($notification->status === 'sent') {
                $targetUsers = collect();
                
                switch ($notification->target_type) {
                    case 'all_users':
                        $targetUsers = User::all();
                        break;
                        
                    case 'specific_users':
                        if (!empty($notification->target_user_ids)) {
                            $targetUsers = User::whereIn('id', $notification->target_user_ids)->get();
                        }
                        break;
                        
                    case 'role_based':
                        if (!empty($notification->target_role_ids)) {
                            $targetUsers = User::whereHas('roles', function ($query) use ($notification) {
                                $query->whereIn('roles.id', $notification->target_role_ids);
                            })->get();
                        }
                        break;
                }
                
                // Create user notification records
                foreach ($targetUsers as $user) {
                    $user->notifications()->attach($notification->id, [
                        'is_read' => rand(0, 1) === 1, // Randomly mark some as read for testing
                        'read_at' => rand(0, 1) === 1 ? Carbon::now()->subMinutes(rand(1, 120)) : null,
                        'is_dismissed' => rand(0, 3) === 1, // Randomly dismiss some
                        'dismissed_at' => rand(0, 3) === 1 ? Carbon::now()->subMinutes(rand(1, 60)) : null,
                        'created_at' => $notification->sent_at ?? $notification->created_at,
                        'updated_at' => $notification->sent_at ?? $notification->created_at,
                    ]);
                }
            }
        }
        
        $this->command->info('Test notifications created successfully!');
        $this->command->info('Created ' . count($notifications) . ' test notifications.');
        
        // Display summary
        $sentCount = Notification::where('status', 'sent')->count();
        $scheduledCount = Notification::where('status', 'scheduled')->count();
        $draftCount = Notification::where('status', 'draft')->count();
        
        $this->command->table(
            ['Status', 'Count'],
            [
                ['Sent', $sentCount],
                ['Scheduled', $scheduledCount],
                ['Draft', $draftCount],
            ]
        );
    }
}