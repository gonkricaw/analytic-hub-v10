<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * SessionSeeder
 * 
 * Seeds the database with realistic session data for testing the online users view.
 * Creates active and inactive sessions with various timestamps to test different
 * online status scenarios (online, away, idle, offline).
 * 
 * @package Database\Seeders
 * @author Analytics Hub Team
 * @version 1.0.0
 */
class SessionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     * 
     * Creates realistic session data:
     * - Active sessions (within 5 minutes) - "online" status
     * - Recent sessions (5-15 minutes) - "away" status  
     * - Idle sessions (15-30 minutes) - "idle" status
     * - Old sessions (30+ minutes) - "offline" status
     * - Multiple sessions per user for testing
     * - Various IP addresses and user agents
     *
     * @return void
     */
    public function run(): void
    {
        try {
            DB::beginTransaction();

            Log::info('Starting session seeding process');

            // Clear existing sessions
            DB::table('sessions')->truncate();

            // Get all test users
            $users = User::whereIn('email', [
                'superadmin@analyticshub.com',
                'admin@analyticshub.com',
                'sarah.wilson@analyticshub.com',
                'michael.johnson@analyticshub.com',
                'emily.davis@analyticshub.com',
                'david.brown@analyticshub.com',
                'lisa.garcia@analyticshub.com',
                'robert.miller@analyticshub.com',
                'jennifer.taylor@analyticshub.com',
                'guest@analyticshub.com'
            ])->get();

            if ($users->isEmpty()) {
                Log::warning('No users found for session seeding. Run UserSeeder first.');
                return;
            }

            $userAgents = [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
                'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Edge/120.0.0.0 Safari/537.36'
            ];

            $ipAddresses = [
                '192.168.1.100', '192.168.1.101', '192.168.1.102', '192.168.1.103',
                '192.168.1.104', '192.168.1.105', '192.168.1.106', '192.168.1.107',
                '10.0.0.50', '10.0.0.51', '10.0.0.52', '10.0.0.53',
                '172.16.0.10', '172.16.0.11', '172.16.0.12', '172.16.0.13'
            ];

            $sessions = [];
            $sessionCount = 0;

            foreach ($users as $user) {
                // Determine how many sessions this user should have (1-3)
                $userSessionCount = rand(1, 3);
                
                for ($i = 0; $i < $userSessionCount; $i++) {
                    // Create different session scenarios
                    $scenario = $this->getSessionScenario($i, $user->email);
                    
                    $sessionId = Str::random(40);
                    $payload = base64_encode(serialize([
                        '_token' => Str::random(40),
                        '_previous' => ['url' => config('app.url') . '/dashboard'],
                        '_flash' => ['old' => [], 'new' => []],
                        'login_web_' . sha1('web') => $user->id,
                        'password_hash_web' => $user->password
                    ]));

                    $sessions[] = [
                        'id' => $sessionId,
                        'user_id' => $user->id,
                        'ip_address' => $ipAddresses[array_rand($ipAddresses)],
                        'user_agent' => $userAgents[array_rand($userAgents)],
                        'payload' => $payload,
                        'last_activity' => $scenario['last_activity']
                    ];
                    
                    $sessionCount++;
                }
            }

            // Insert sessions in batches
            $chunks = array_chunk($sessions, 50);
            foreach ($chunks as $chunk) {
                DB::table('sessions')->insert($chunk);
            }

            DB::commit();

            Log::info("Session seeding completed successfully. Created {$sessionCount} sessions.");
            
            // Display session distribution summary
            $this->displaySessionSummary();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Session seeding failed: ' . $e->getMessage());
            $this->command->error('Session seeding failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get session scenario based on user and session index
     * 
     * Creates different timing scenarios to test all online status types:
     * - Online: last activity within 5 minutes
     * - Away: last activity 5-15 minutes ago
     * - Idle: last activity 15-30 minutes ago
     * - Offline: last activity 30+ minutes ago
     *
     * @param int $sessionIndex
     * @param string $userEmail
     * @return array
     */
    private function getSessionScenario(int $sessionIndex, string $userEmail): array
    {
        $now = Carbon::now();
        
        // Super admin and admin users are more likely to be online
        $isHighPriorityUser = in_array($userEmail, [
            'superadmin@analyticshub.com',
            'admin@analyticshub.com'
        ]);

        // Primary session (index 0) is usually more recent
        if ($sessionIndex === 0) {
            if ($isHighPriorityUser) {
                // High priority users: 70% online, 20% away, 10% idle
                $rand = rand(1, 100);
                if ($rand <= 70) {
                    // Online (within 5 minutes)
                    $lastActivity = $now->subMinutes(rand(0, 4))->timestamp;
                } elseif ($rand <= 90) {
                    // Away (5-15 minutes)
                    $lastActivity = $now->subMinutes(rand(5, 14))->timestamp;
                } else {
                    // Idle (15-30 minutes)
                    $lastActivity = $now->subMinutes(rand(15, 29))->timestamp;
                }
            } else {
                // Regular users: 40% online, 30% away, 20% idle, 10% offline
                $rand = rand(1, 100);
                if ($rand <= 40) {
                    // Online
                    $lastActivity = $now->subMinutes(rand(0, 4))->timestamp;
                } elseif ($rand <= 70) {
                    // Away
                    $lastActivity = $now->subMinutes(rand(5, 14))->timestamp;
                } elseif ($rand <= 90) {
                    // Idle
                    $lastActivity = $now->subMinutes(rand(15, 29))->timestamp;
                } else {
                    // Offline
                    $lastActivity = $now->subMinutes(rand(31, 120))->timestamp;
                }
            }
            
            $createdAt = $now->subHours(rand(1, 8));
        } else {
            // Secondary sessions are usually older
            $lastActivity = $now->subMinutes(rand(20, 180))->timestamp;
            $createdAt = $now->subHours(rand(2, 24));
        }

        return [
            'last_activity' => $lastActivity,
            'created_at' => $createdAt,
            'updated_at' => Carbon::createFromTimestamp($lastActivity)
        ];
    }

    /**
     * Display session distribution summary
     * 
     * Shows how many sessions fall into each online status category
     * for testing verification.
     *
     * @return void
     */
    private function displaySessionSummary(): void
    {
        $now = Carbon::now();
        
        // Count sessions by status
        $online = DB::table('sessions')
            ->where('last_activity', '>=', $now->subMinutes(5)->timestamp)
            ->count();
            
        $away = DB::table('sessions')
            ->where('last_activity', '>=', $now->subMinutes(15)->timestamp)
            ->where('last_activity', '<', $now->subMinutes(5)->timestamp)
            ->count();
            
        $idle = DB::table('sessions')
            ->where('last_activity', '>=', $now->subMinutes(30)->timestamp)
            ->where('last_activity', '<', $now->subMinutes(15)->timestamp)
            ->count();
            
        $offline = DB::table('sessions')
            ->where('last_activity', '<', $now->subMinutes(30)->timestamp)
            ->count();

        $total = $online + $away + $idle + $offline;

        $this->command->info('\n=== Session Distribution Summary ===');
        $this->command->info("📊 Total Sessions: {$total}");
        $this->command->info("🟢 Online (0-5 min): {$online}");
        $this->command->info("🟡 Away (5-15 min): {$away}");
        $this->command->info("🟠 Idle (15-30 min): {$idle}");
        $this->command->info("🔴 Offline (30+ min): {$offline}");
        $this->command->info('\n=== Testing Ready ===');
        $this->command->info('• Online Users View populated with test data');
        $this->command->info('• Multiple session scenarios available');
        $this->command->info('• Different user activity patterns created');
        $this->command->info('• Ready for widget testing and development');
    }
}