<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

/**
 * DatabaseSeeder
 * 
 * Main seeder class that orchestrates all database seeding.
 * Runs seeders in the correct order to maintain referential integrity.
 * 
 * @package Database\Seeders
 * @author Analytics Hub Team
 * @version 1.0.0
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * 
     * Runs all seeders in the correct order:
     * 1. System configuration and templates
     * 2. Roles and permissions (RBAC foundation)
     * 3. Role-permission relationships
     * 4. Users with role assignments
     * 5. Menus with permission requirements
     * 6. Content with role restrictions
     *
     * @return void
     */
    public function run(): void
    {
        Log::info('Starting complete database seeding process');
        
        try {
            // Step 1: System Configuration and Templates
            $this->call([
                TermsConfigSeeder::class,
                InvitationEmailTemplateSeeder::class,
                TermsEmailTemplateSeeder::class,
            ]);
            
            // Step 2: RBAC Foundation - Roles and Permissions
            $this->call([
                RoleSeeder::class,
                PermissionSeeder::class,
            ]);
            
            // Step 3: RBAC Relationships
            $this->call([
                RolePermissionSeeder::class,
            ]);
            
            // Step 4: Users with Role Assignments
            $this->call([
                UserSeeder::class,
            ]);
            
            // Step 5: Session Data for Online Users Testing
            $this->call([
                SessionSeeder::class,
            ]);
            
            // Step 6: Content with Role Restrictions
            $this->call([
                ContentSeeder::class,
            ]);
            
            // Step 7: Activity Data for Analytics and Dashboard Widgets
            $this->call([
                ActivitySeeder::class,
            ]);
            
            // Step 8: Menus with Permission Requirements
            $this->call([
                MenuSeeder::class,
            ]);
            
            Log::info('Complete database seeding process completed successfully');
            
            // Display seeding summary
            $this->command->info('\n=== Database Seeding Summary ===');
            $this->command->info('✓ System configuration and email templates');
            $this->command->info('✓ Roles: Super Admin, Admin, Manager, User, Guest');
            $this->command->info('✓ Permissions: 25+ permissions across all modules');
            $this->command->info('✓ Role-Permission assignments');
            $this->command->info('✓ Test users: 10 users with different roles');
            $this->command->info('✓ Session data: Realistic online/away/idle/offline scenarios');
            $this->command->info('✓ Activity data: Login attempts, user activities, content views');
            $this->command->info('✓ Navigation menus with hierarchical structure');
            $this->command->info('✓ Sample content: 7 content items with role restrictions');
            $this->command->info('\n=== Test Accounts ===');
            $this->command->info('Super Admin: superadmin@analyticshub.com / password123');
            $this->command->info('Admin: admin@analyticshub.com / password123');
            $this->command->info('Manager: michael.johnson@analyticshub.com / password123');
            $this->command->info('User: david.brown@analyticshub.com / password123');
            $this->command->info('Guest: guest@analyticshub.com / password123');
            $this->command->info('Test: test@example.com / password');
            $this->command->info('\n=== Ready for Testing ===');
            $this->command->info('• Menu active state detection');
            $this->command->info('• Breadcrumb generation');
            $this->command->info('• Role-based access control');
            $this->command->info('• Dynamic navigation rendering');
            $this->command->info('• Content management with permissions');
            $this->command->info('• Online users widget with real session data');
            $this->command->info('• Dashboard widgets with various activity levels');
            $this->command->info('• Analytics dashboard with login trends and user activity');
            $this->command->info('• Activity tracking and reporting features');
            
        } catch (\Exception $e) {
            Log::error('Database seeding failed: ' . $e->getMessage());
            $this->command->error('Database seeding failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
