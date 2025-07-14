<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Content;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * ActivitySeeder
 * 
 * Seeds the database with realistic user activity data for testing dashboard widgets
 * and analytics views. Creates login attempts, user activities, and content views
 * with realistic patterns and timestamps.
 * 
 * @package Database\Seeders
 * @author Analytics Hub Team
 * @version 1.0.0
 */
class ActivitySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     * 
     * Creates realistic activity data:
     * - Login attempts (successful and failed)
     * - User activities (page views, content access, etc.)
     * - Content view tracking
     * - Activity patterns over the last 30 days
     * - Different activity levels per user type
     *
     * @return void
     */
    public function run(): void
    {
        try {
            DB::beginTransaction();

            Log::info('Starting activity seeding process');

            // Clear existing activity data
            DB::table('idbi_login_attempts')->truncate();
            DB::table('idbi_user_activities')->truncate();

            // Get users and content for activity generation
            $users = User::all();
            $contents = Content::all();

            if ($users->isEmpty()) {
                Log::warning('No users found for activity seeding. Run UserSeeder first.');
                return;
            }

            // Generate login attempts for the last 30 days
            $this->generateLoginAttempts($users);

            // Generate user activities for the last 30 days
            $this->generateUserActivities($users, $contents);

            DB::commit();

            Log::info('Activity seeding completed successfully.');
            $this->displayActivitySummary();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Activity seeding failed: ' . $e->getMessage());
            $this->command->error('Activity seeding failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate realistic login attempts for users
     * 
     * Creates both successful and failed login attempts with realistic patterns:
     * - More activity during business hours
     * - Different patterns for different user types
     * - Some failed attempts to test security features
     * - Weekend vs weekday patterns
     *
     * @param \Illuminate\Database\Eloquent\Collection $users
     * @return void
     */
    private function generateLoginAttempts($users): void
    {
        $loginAttempts = [];
        $ipAddresses = [
            '192.168.1.100', '192.168.1.101', '192.168.1.102', '192.168.1.103',
            '10.0.0.50', '10.0.0.51', '172.16.0.10', '172.16.0.11',
            '203.0.113.1', '198.51.100.1' // Some external IPs for variety
        ];

        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0'
        ];

        foreach ($users as $user) {
            // Determine user activity level based on role
            $activityLevel = $this->getUserActivityLevel($user->email);
            
            // Generate login attempts for the last 30 days
            for ($day = 30; $day >= 0; $day--) {
                $date = Carbon::now()->subDays($day);
                
                // Skip some days randomly (users don't login every day)
                if (rand(1, 100) <= 30) continue;
                
                // Determine number of logins for this day
                $loginsToday = $this->getLoginsForDay($activityLevel, $date);
                
                for ($login = 0; $login < $loginsToday; $login++) {
                    // Generate realistic login time (business hours bias)
                    $loginTime = $this->getRealisticLoginTime($date);
                    
                    // 95% success rate, 5% failed attempts
                    $isSuccessful = rand(1, 100) <= 95;
                    
                    $loginAttempts[] = [
                        'id' => Str::uuid(),
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'status' => $isSuccessful ? 'success' : 'failed',
                        'ip_address' => $ipAddresses[array_rand($ipAddresses)],
                        'user_agent' => $userAgents[array_rand($userAgents)],
                        'attempted_at' => $loginTime,
                        'session_id' => $isSuccessful ? Str::random(40) : null,
                        'failure_reason' => $isSuccessful ? null : 'invalid_credentials',
                        'created_at' => $loginTime,
                        'updated_at' => $loginTime
                    ];
                }
            }
        }

        // Insert login attempts in batches
        $chunks = array_chunk($loginAttempts, 100);
        foreach ($chunks as $chunk) {
            DB::table('idbi_login_attempts')->insert($chunk);
        }

        Log::info('Generated ' . count($loginAttempts) . ' login attempts');
    }

    /**
     * Generate realistic user activities
     * 
     * Creates various types of user activities:
     * - Page views
     * - Content access
     * - Menu navigation
     * - Profile updates
     * - System interactions
     *
     * @param \Illuminate\Database\Eloquent\Collection $users
     * @param \Illuminate\Database\Eloquent\Collection $contents
     * @return void
     */
    private function generateUserActivities($users, $contents): void
    {
        $activities = [];
        $activityTypes = [
            'page_view' => 40,      // 40% of activities
            'content_view' => 25,   // 25% of activities
            'menu_click' => 15,     // 15% of activities
            'profile_update' => 5,  // 5% of activities
            'search' => 10,         // 10% of activities
            'download' => 3,        // 3% of activities
            'export' => 2           // 2% of activities
        ];

        $pages = [
            '/dashboard', '/profile', '/users', '/roles', '/permissions',
            '/menus', '/content', '/reports', '/settings', '/help'
        ];

        foreach ($users as $user) {
            $activityLevel = $this->getUserActivityLevel($user->email);
            
            // Generate activities for the last 30 days
            for ($day = 30; $day >= 0; $day--) {
                $date = Carbon::now()->subDays($day);
                
                // Skip some days
                if (rand(1, 100) <= 20) continue;
                
                // Determine number of activities for this day
                $activitiesToday = $this->getActivitiesForDay($activityLevel, $date);
                
                for ($activity = 0; $activity < $activitiesToday; $activity++) {
                    // Generate realistic activity time
                    $activityTime = $this->getRealisticActivityTime($date);
                    
                    // Choose activity type based on weights
                    $activityType = $this->getWeightedActivityType($activityTypes);
                    
                    // Generate activity data based on type
                    $activityData = $this->generateActivityData($activityType, $user, $contents, $pages);
                    
                    $activities[] = [
                        'id' => Str::uuid(),
                        'user_id' => $user->id,
                        'activity_type' => $activityType,
                        'activity_name' => ucfirst(str_replace('_', ' ', $activityType)),
                        'subject_type' => $activityData['subject_type'],
                        'subject_id' => $activityData['subject_id'],
                        'description' => $activityData['description'],
                        'properties' => json_encode($activityData['properties']),
                        'ip_address' => '192.168.1.' . rand(100, 200),
                        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                        'session_id' => Str::random(40),
                        'created_at' => $activityTime,
                        'updated_at' => $activityTime
                    ];
                }
            }
        }

        // Insert activities in batches
        $chunks = array_chunk($activities, 100);
        foreach ($chunks as $chunk) {
            DB::table('idbi_user_activities')->insert($chunk);
        }

        Log::info('Generated ' . count($activities) . ' user activities');
    }

    /**
     * Get user activity level based on email/role
     *
     * @param string $email
     * @return string
     */
    private function getUserActivityLevel(string $email): string
    {
        if (in_array($email, ['superadmin@analyticshub.com', 'admin@analyticshub.com'])) {
            return 'high';
        } elseif (str_contains($email, 'manager') || str_contains($email, 'michael') || str_contains($email, 'emily')) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Get number of logins for a specific day based on activity level
     *
     * @param string $activityLevel
     * @param Carbon $date
     * @return int
     */
    private function getLoginsForDay(string $activityLevel, Carbon $date): int
    {
        $isWeekend = $date->isWeekend();
        
        switch ($activityLevel) {
            case 'high':
                return $isWeekend ? rand(0, 2) : rand(1, 4);
            case 'medium':
                return $isWeekend ? rand(0, 1) : rand(1, 3);
            case 'low':
                return $isWeekend ? rand(0, 1) : rand(0, 2);
            default:
                return 1;
        }
    }

    /**
     * Get number of activities for a specific day
     *
     * @param string $activityLevel
     * @param Carbon $date
     * @return int
     */
    private function getActivitiesForDay(string $activityLevel, Carbon $date): int
    {
        $isWeekend = $date->isWeekend();
        
        switch ($activityLevel) {
            case 'high':
                return $isWeekend ? rand(5, 15) : rand(20, 50);
            case 'medium':
                return $isWeekend ? rand(2, 8) : rand(10, 25);
            case 'low':
                return $isWeekend ? rand(0, 3) : rand(3, 12);
            default:
                return rand(1, 5);
        }
    }

    /**
     * Generate realistic login time (business hours bias)
     *
     * @param Carbon $date
     * @return Carbon
     */
    private function getRealisticLoginTime(Carbon $date): Carbon
    {
        // 70% chance of business hours (8 AM - 6 PM)
        if (rand(1, 100) <= 70) {
            $hour = rand(8, 17);
        } else {
            // 30% chance of other hours
            $hour = rand(6, 23);
        }
        
        return $date->copy()->setTime($hour, rand(0, 59), rand(0, 59));
    }

    /**
     * Generate realistic activity time
     *
     * @param Carbon $date
     * @return Carbon
     */
    private function getRealisticActivityTime(Carbon $date): Carbon
    {
        // Activities spread throughout the day but bias toward business hours
        if (rand(1, 100) <= 60) {
            $hour = rand(9, 17);
        } else {
            $hour = rand(7, 22);
        }
        
        return $date->copy()->setTime($hour, rand(0, 59), rand(0, 59));
    }

    /**
     * Get weighted activity type
     *
     * @param array $weights
     * @return string
     */
    private function getWeightedActivityType(array $weights): string
    {
        $rand = rand(1, 100);
        $cumulative = 0;
        
        foreach ($weights as $type => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $type;
            }
        }
        
        return 'page_view'; // fallback
    }

    /**
     * Generate activity data based on type
     *
     * @param string $type
     * @param User $user
     * @param \Illuminate\Database\Eloquent\Collection $contents
     * @param array $pages
     * @return array
     */
    private function generateActivityData(string $type, User $user, $contents, array $pages): array
    {
        switch ($type) {
            case 'content_view':
                $content = $contents->random();
                return [
                    'subject_type' => 'App\\Models\\Content',
                    'subject_id' => $content->id,
                    'description' => "Viewed content: {$content->title}",
                    'properties' => [
                        'content_type' => $content->type,
                        'content_slug' => $content->slug,
                        'view_duration' => rand(30, 300) // seconds
                    ]
                ];
                
            case 'menu_click':
                $menuItems = ['Dashboard', 'Users', 'Roles', 'Content', 'Reports', 'Settings'];
                $menu = $menuItems[array_rand($menuItems)];
                return [
                    'subject_type' => 'menu',
                    'subject_id' => null,
                    'description' => "Clicked menu: {$menu}",
                    'properties' => [
                        'menu_item' => $menu,
                        'menu_level' => rand(0, 2)
                    ]
                ];
                
            case 'profile_update':
                return [
                    'subject_type' => 'App\\Models\\User',
                    'subject_id' => $user->id,
                    'description' => 'Updated profile information',
                    'properties' => [
                        'fields_updated' => ['bio', 'phone'],
                        'update_type' => 'profile'
                    ]
                ];
                
            case 'search':
                $searchTerms = ['user', 'content', 'report', 'dashboard', 'analytics', 'data'];
                $term = $searchTerms[array_rand($searchTerms)];
                return [
                    'subject_type' => 'search',
                    'subject_id' => null,
                    'description' => "Searched for: {$term}",
                    'properties' => [
                        'search_term' => $term,
                        'results_count' => rand(0, 50)
                    ]
                ];
                
            case 'download':
                return [
                    'subject_type' => 'file',
                    'subject_id' => null,
                    'description' => 'Downloaded report file',
                    'properties' => [
                        'file_type' => 'pdf',
                        'file_size' => rand(100, 5000) . 'KB'
                    ]
                ];
                
            case 'export':
                return [
                    'subject_type' => 'export',
                    'subject_id' => null,
                    'description' => 'Exported data to CSV',
                    'properties' => [
                        'export_type' => 'csv',
                        'records_count' => rand(10, 1000)
                    ]
                ];
                
            default: // page_view
                $page = $pages[array_rand($pages)];
                return [
                    'subject_type' => 'page',
                    'subject_id' => null,
                    'description' => "Viewed page: {$page}",
                    'properties' => [
                        'page_url' => $page,
                        'page_load_time' => rand(200, 2000) . 'ms'
                    ]
                ];
        }
    }

    /**
     * Display activity summary
     *
     * @return void
     */
    private function displayActivitySummary(): void
    {
        $loginCount = DB::table('idbi_login_attempts')->count();
        $activityCount = DB::table('idbi_user_activities')->count();
        $successfulLogins = DB::table('idbi_login_attempts')->where('status', 'success')->count();
        $failedLogins = DB::table('idbi_login_attempts')->where('status', 'failed')->count();
        
        // Get activity type distribution
        $activityTypes = DB::table('idbi_user_activities')
            ->select('activity_type', DB::raw('count(*) as count'))
            ->groupBy('activity_type')
            ->orderBy('count', 'desc')
            ->get();

        $this->command->info('\n=== Activity Data Summary ===');
        $this->command->info("📊 Total Login Attempts: {$loginCount}");
        $this->command->info("✅ Successful Logins: {$successfulLogins}");
        $this->command->info("❌ Failed Logins: {$failedLogins}");
        $this->command->info("🎯 Total User Activities: {$activityCount}");
        
        $this->command->info('\n=== Activity Types ===');
        foreach ($activityTypes as $type) {
            $this->command->info("• {$type->activity_type}: {$type->count}");
        }
        
        $this->command->info('\n=== Analytics Ready ===');
        $this->command->info('• Login trends data populated');
        $this->command->info('• User activity patterns created');
        $this->command->info('• Content view tracking available');
        $this->command->info('• Dashboard widgets ready for testing');
    }
}