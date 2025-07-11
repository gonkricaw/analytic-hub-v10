<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * UserSeeder
 * 
 * Seeds the database with test users for different roles.
 * Creates realistic user data for testing the system.
 * 
 * @package Database\Seeders
 * @author Analytics Hub Team
 * @version 1.0.0
 */
class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     * 
     * Creates test users:
     * - Super Admin user
     * - Admin users
     * - Manager users
     * - Regular users
     * - Guest users
     *
     * @return void
     */
    public function run(): void
    {
        try {
            DB::beginTransaction();

            Log::info('Starting user seeding process');

            // Clear existing test users (keep system users)
            User::whereNotIn('email', ['system@analyticshub.com'])->delete();

            $users = [
                // Super Admin
                [
                    'first_name' => 'Super',
                    'last_name' => 'Administrator',
                    'email' => 'superadmin@analyticshub.com',
                    'username' => 'superadmin',
                    'password' => Hash::make('password123'),
                    'email_verified_at' => now(),
                    'is_first_login' => false,
                    'password_changed_at' => now()->subDays(30),
                    'last_login_at' => now()->subHours(2),
                    'last_login_ip' => '192.168.1.100',
                    'status' => 'active',
                    'terms_accepted' => true,
                    'terms_accepted_at' => now()->subDays(30),
                    'email_notifications' => true,
                    'bio' => 'System Super Administrator with full access to all features.',
                    'phone' => '+1-555-0001',
                    'department' => 'IT Administration',
                    'position' => 'Super Administrator',
                    'failed_login_attempts' => 0,
                    'role' => 'super_admin'
                ],

                // Admin Users
                [
                    'first_name' => 'John',
                    'last_name' => 'Admin',
                    'email' => 'admin@analyticshub.com',
                    'username' => 'johnadmin',
                    'password' => Hash::make('password123'),
                    'email_verified_at' => now(),
                    'is_first_login' => false,
                    'password_changed_at' => now()->subDays(15),
                    'last_login_at' => now()->subHours(1),
                    'last_login_ip' => '192.168.1.101',
                    'status' => 'active',
                    'terms_accepted' => true,
                    'terms_accepted_at' => now()->subDays(25),
                    'email_notifications' => true,
                    'bio' => 'System Administrator responsible for user management and system maintenance.',
                    'phone' => '+1-555-0002',
                    'department' => 'IT Administration',
                    'position' => 'System Administrator',
                    'failed_login_attempts' => 0,
                    'role' => 'admin'
                ],
                [
                    'first_name' => 'Sarah',
                    'last_name' => 'Wilson',
                    'email' => 'sarah.wilson@analyticshub.com',
                    'username' => 'sarahw',
                    'password' => Hash::make('password123'),
                    'email_verified_at' => now(),
                    'is_first_login' => false,
                    'password_changed_at' => now()->subDays(20),
                    'last_login_at' => now()->subHours(3),
                    'last_login_ip' => '192.168.1.102',
                    'status' => 'active',
                    'terms_accepted' => true,
                    'terms_accepted_at' => now()->subDays(22),
                    'email_notifications' => true,
                    'bio' => 'Administrative support specialist.',
                    'phone' => '+1-555-0003',
                    'department' => 'Administration',
                    'position' => 'Admin Specialist',
                    'failed_login_attempts' => 0,
                    'role' => 'admin'
                ],

                // Manager Users
                [
                    'first_name' => 'Michael',
                    'last_name' => 'Johnson',
                    'email' => 'michael.johnson@analyticshub.com',
                    'username' => 'michaelj',
                    'password' => Hash::make('password123'),
                    'email_verified_at' => now(),
                    'is_first_login' => false,
                    'password_changed_at' => now()->subDays(10),
                    'last_login_at' => now()->subMinutes(30),
                    'last_login_ip' => '192.168.1.103',
                    'status' => 'active',
                    'terms_accepted' => true,
                    'terms_accepted_at' => now()->subDays(18),
                    'email_notifications' => true,
                    'bio' => 'Content Manager responsible for content strategy and management.',
                    'phone' => '+1-555-0004',
                    'department' => 'Content Management',
                    'position' => 'Content Manager',
                    'failed_login_attempts' => 0,
                    'role' => 'manager'
                ],
                [
                    'first_name' => 'Emily',
                    'last_name' => 'Davis',
                    'email' => 'emily.davis@analyticshub.com',
                    'username' => 'emilyd',
                    'password' => Hash::make('password123'),
                    'email_verified_at' => now(),
                    'is_first_login' => false,
                    'password_changed_at' => now()->subDays(12),
                    'last_login_at' => now()->subHours(4),
                    'last_login_ip' => '192.168.1.104',
                    'status' => 'active',
                    'terms_accepted' => true,
                    'terms_accepted_at' => now()->subDays(16),
                    'email_notifications' => true,
                    'bio' => 'Analytics Manager overseeing data analysis and reporting.',
                    'phone' => '+1-555-0005',
                    'department' => 'Analytics',
                    'position' => 'Analytics Manager',
                    'failed_login_attempts' => 0,
                    'role' => 'manager'
                ],

                // Regular Users
                [
                    'first_name' => 'David',
                    'last_name' => 'Brown',
                    'email' => 'david.brown@analyticshub.com',
                    'username' => 'davidb',
                    'password' => Hash::make('password123'),
                    'email_verified_at' => now(),
                    'is_first_login' => false,
                    'password_changed_at' => now()->subDays(8),
                    'last_login_at' => now()->subMinutes(15),
                    'last_login_ip' => '192.168.1.105',
                    'status' => 'active',
                    'terms_accepted' => true,
                    'terms_accepted_at' => now()->subDays(14),
                    'email_notifications' => true,
                    'bio' => 'Content Creator responsible for creating and editing content.',
                    'phone' => '+1-555-0006',
                    'department' => 'Content',
                    'position' => 'Content Creator',
                    'failed_login_attempts' => 0,
                    'role' => 'user'
                ],
                [
                    'first_name' => 'Lisa',
                    'last_name' => 'Garcia',
                    'email' => 'lisa.garcia@analyticshub.com',
                    'username' => 'lisag',
                    'password' => Hash::make('password123'),
                    'email_verified_at' => now(),
                    'is_first_login' => false,
                    'password_changed_at' => now()->subDays(6),
                    'last_login_at' => now()->subMinutes(45),
                    'last_login_ip' => '192.168.1.106',
                    'status' => 'active',
                    'terms_accepted' => true,
                    'terms_accepted_at' => now()->subDays(12),
                    'email_notifications' => true,
                    'bio' => 'Data Analyst working with reports and analytics.',
                    'phone' => '+1-555-0007',
                    'department' => 'Analytics',
                    'position' => 'Data Analyst',
                    'failed_login_attempts' => 0,
                    'role' => 'user'
                ],
                [
                    'first_name' => 'Robert',
                    'last_name' => 'Miller',
                    'email' => 'robert.miller@analyticshub.com',
                    'username' => 'robertm',
                    'password' => Hash::make('password123'),
                    'email_verified_at' => now(),
                    'is_first_login' => true,
                    'password_changed_at' => null,
                    'last_login_at' => null,
                    'last_login_ip' => null,
                    'status' => 'active',
                    'terms_accepted' => true,
                    'terms_accepted_at' => now()->subDays(2),
                    'email_notifications' => true,
                    'bio' => 'New team member in the content department.',
                    'phone' => '+1-555-0008',
                    'department' => 'Content',
                    'position' => 'Content Assistant',
                    'failed_login_attempts' => 0,
                    'role' => 'user'
                ],

                // Guest Users
                [
                    'first_name' => 'Guest',
                    'last_name' => 'User',
                    'email' => 'guest@analyticshub.com',
                    'username' => 'guest',
                    'password' => Hash::make('password123'),
                    'email_verified_at' => now(),
                    'is_first_login' => false,
                    'password_changed_at' => now()->subDays(5),
                    'last_login_at' => now()->subHours(6),
                    'last_login_ip' => '192.168.1.107',
                    'status' => 'active',
                    'terms_accepted' => true,
                    'terms_accepted_at' => now()->subDays(10),
                    'email_notifications' => false,
                    'bio' => 'Guest user with limited access.',
                    'phone' => '+1-555-0009',
                    'department' => 'External',
                    'position' => 'Guest',
                    'failed_login_attempts' => 0,
                    'role' => 'guest'
                ],

                // Test User (for development)
                [
                    'first_name' => 'Test',
                    'last_name' => 'User',
                    'email' => 'test@example.com',
                    'username' => 'testuser',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'is_first_login' => false,
                    'password_changed_at' => now()->subDays(1),
                    'last_login_at' => now()->subMinutes(5),
                    'last_login_ip' => '127.0.0.1',
                    'status' => 'active',
                    'terms_accepted' => true,
                    'terms_accepted_at' => now()->subDays(1),
                    'email_notifications' => true,
                    'bio' => 'Test user for development and testing purposes.',
                    'phone' => '+1-555-0000',
                    'department' => 'Development',
                    'position' => 'Test User',
                    'failed_login_attempts' => 0,
                    'role' => 'user'
                ]
            ];

            // Get all roles first
            $roles = Role::all()->keyBy('name');
            
            if ($roles->isEmpty()) {
                Log::warning('No roles found. Skipping user creation.');
                DB::rollBack();
                return;
            }
            
            // Create users and assign roles
            foreach ($users as $userData) {
                $roleName = $userData['role'];
                unset($userData['role']);
                
                $userData['id'] = \Illuminate\Support\Str::uuid();
                $user = User::create($userData);
                
                // Assign role to user
                $role = $roles->get($roleName);
                if ($role) {
                    DB::table('idbi_user_roles')->insert([
                        'id' => \Illuminate\Support\Str::uuid(),
                        'user_id' => $user->id,
                        'role_id' => $role->id,
                        'assigned_by' => null, // System assignment
                        'assigned_at' => now(),
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    Log::info("Created user: {$user->email} with role: {$roleName} (ID: {$user->id})");
                } else {
                    Log::warning("Role '{$roleName}' not found for user {$user->email}");
                }
            }

            DB::commit();
            Log::info('User seeding completed successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User seeding failed: ' . $e->getMessage());
            throw $e;
        }
    }
}