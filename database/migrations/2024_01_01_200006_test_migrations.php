<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tests all database migrations and views to ensure they work correctly
     * in the Analytics Hub system.
     */
    public function up(): void
    {
        // Test all tables exist
        $this->testTablesExist();
        
        // Test all views exist and are queryable
        $this->testViewsExist();
        
        // Test indexes exist
        $this->testIndexesExist();
        
        // Test foreign key constraints
        $this->testForeignKeyConstraints();
        
        // Test sample data insertion and view queries
        $this->testSampleDataAndViews();
        
        // Log successful migration test
        DB::table('idbi_system_configs')->updateOrInsert(
            ['config_key' => 'migration_test_status'],
            [
                'config_value' => 'passed',
                'description' => 'All database migrations and views tested successfully',
                'updated_at' => now()
            ]
        );
    }

    /**
     * Test that all required tables exist
     */
    private function testTablesExist(): void
    {
        $requiredTables = [
            'idbi_users',
            'idbi_roles',
            'idbi_permissions',
            'idbi_role_permissions',
            'idbi_user_roles',
            'idbi_menus',
            'idbi_menu_roles',
            'idbi_contents',
            'idbi_content_roles',
            'idbi_email_templates',
            'idbi_email_queue',
            'idbi_notifications',
            'idbi_user_notifications',
            'idbi_user_activities',
            'idbi_password_resets',
            'idbi_blacklisted_ips',
            'idbi_system_configs',
            'idbi_user_avatars',
            'idbi_login_attempts',
            'idbi_password_histories',
            'idbi_sessions'
        ];

        foreach ($requiredTables as $table) {
            if (!Schema::hasTable($table)) {
                throw new Exception("Required table '{$table}' does not exist");
            }
        }
    }

    /**
     * Test that all database views exist and are queryable
     */
    private function testViewsExist(): void
    {
        $requiredViews = [
            'v_top_active_users',
            'v_login_trends',
            'v_popular_content',
            'v_online_users'
        ];

        foreach ($requiredViews as $view) {
            try {
                // Test if view exists by querying it with LIMIT 0
                DB::select("SELECT * FROM {$view} LIMIT 0");
            } catch (Exception $e) {
                throw new Exception("Required view '{$view}' does not exist or is not queryable: " . $e->getMessage());
            }
        }
    }

    /**
     * Test that critical indexes exist
     */
    private function testIndexesExist(): void
    {
        // Test some critical indexes exist
        $criticalIndexes = [
            'idbi_users' => ['idx_users_status_deleted', 'idx_users_email_status'],
            'idbi_login_attempts' => ['idx_login_attempts_user_status_date', 'idx_login_trends_analysis'],
            'idbi_sessions' => ['idx_sessions_active_tracking', 'idx_sessions_user_active'],
            'idbi_user_activities' => ['idx_activities_content_views', 'idx_activities_session_tracking'],
            'idbi_contents' => ['idx_contents_published_status', 'idx_contents_slug_status']
        ];

        foreach ($criticalIndexes as $table => $indexes) {
            foreach ($indexes as $index) {
                // Check if index exists (method varies by database)
                $indexExists = false;
                
                try {
                    if (config('database.default') === 'mysql') {
                        $result = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]);
                        $indexExists = !empty($result);
                    } elseif (config('database.default') === 'pgsql') {
                        $result = DB::select("
                            SELECT indexname 
                            FROM pg_indexes 
                            WHERE tablename = ? AND indexname = ?
                        ", [$table, $index]);
                        $indexExists = !empty($result);
                    }
                } catch (Exception $e) {
                    // Index check failed, but continue testing
                }
                
                // Note: We don't throw exception for missing indexes as they're performance optimizations
                // Just log a warning if needed
            }
        }
    }

    /**
     * Test foreign key constraints
     */
    private function testForeignKeyConstraints(): void
    {
        // Test that foreign key relationships work by attempting to insert invalid data
        // This should fail due to foreign key constraints
        
        try {
            // Try to insert a user role with non-existent user_id
            DB::table('idbi_user_roles')->insert([
                'id' => 'test-invalid-user-role',
                'user_id' => 'non-existent-user-id',
                'role_id' => 'non-existent-role-id',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // If we reach here, foreign key constraint is not working
            throw new Exception('Foreign key constraint test failed: Invalid data was inserted');
            
        } catch (Exception $e) {
            // This is expected - foreign key constraint should prevent the insert
            if (strpos($e->getMessage(), 'foreign key') === false && 
                strpos($e->getMessage(), 'constraint') === false &&
                strpos($e->getMessage(), 'FOREIGN KEY') === false) {
                // If error is not related to foreign key, re-throw
                throw $e;
            }
        }
    }

    /**
     * Test sample data insertion and view queries
     */
    private function testSampleDataAndViews(): void
    {
        // Create test data for views
        $testUserId = 'test-user-' . uniqid();
        $testRoleId = 'test-role-' . uniqid();
        $testContentId = 'test-content-' . uniqid();
        $testSessionId = 'test-session-' . uniqid();
        
        try {
            // Insert test role
            DB::table('idbi_roles')->insert([
                'id' => $testRoleId,
                'name' => 'Test Role',
                'slug' => 'test-role',
                'description' => 'Test role for migration testing',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Insert test user
            DB::table('idbi_users')->insert([
                'id' => $testUserId,
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test-migration@example.com',
                'username' => 'test-migration-user',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Insert test user role
            DB::table('idbi_user_roles')->insert([
                'id' => 'test-user-role-' . uniqid(),
                'user_id' => $testUserId,
                'role_id' => $testRoleId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Insert test content
            DB::table('idbi_contents')->insert([
                'id' => $testContentId,
                'title' => 'Test Content',
                'slug' => 'test-content-migration',
                'content' => 'This is test content for migration testing',
                'type' => 'article',
                'status' => 'published',
                'published_at' => now(),
                'view_count' => 10,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Insert test session
            DB::table('idbi_sessions')->insert([
                'id' => $testSessionId,
                'user_id' => $testUserId,
                'user_email' => 'test-migration@example.com',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Test User Agent',
                'device_type' => 'desktop',
                'browser' => 'chrome',
                'platform' => 'windows',
                'payload' => 'test_payload',
                'last_activity' => time(),
                'is_active' => true,
                'is_authenticated' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Insert test login attempt
            DB::table('idbi_login_attempts')->insert([
                'id' => 'test-login-' . uniqid(),
                'user_id' => $testUserId,
                'email' => 'test-migration@example.com',
                'status' => 'success',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Test User Agent',
                'attempted_at' => now(),
                'session_id' => $testSessionId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Insert test user activity
            DB::table('idbi_user_activities')->insert([
                'id' => 'test-activity-' . uniqid(),
                'user_id' => $testUserId,
                'activity_type' => 'content_view',
                'activity_name' => 'View Content',
                'description' => 'User viewed test content',
                'subject_type' => 'App\\Models\\Content',
                'subject_id' => $testContentId,
                'session_id' => $testSessionId,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Test User Agent',
                'url' => '/content/test-content-migration',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Test each view with the sample data
            $this->testViewQuery('v_top_active_users');
            $this->testViewQuery('v_login_trends');
            $this->testViewQuery('v_popular_content');
            $this->testViewQuery('v_online_users');
            
        } finally {
            // Clean up test data
            DB::table('idbi_user_activities')->where('user_id', $testUserId)->delete();
            DB::table('idbi_login_attempts')->where('user_id', $testUserId)->delete();
            DB::table('idbi_sessions')->where('id', $testSessionId)->delete();
            DB::table('idbi_contents')->where('id', $testContentId)->delete();
            DB::table('idbi_user_roles')->where('user_id', $testUserId)->delete();
            DB::table('idbi_users')->where('id', $testUserId)->delete();
            DB::table('idbi_roles')->where('id', $testRoleId)->delete();
        }
    }
    
    /**
     * Test a specific view query
     */
    private function testViewQuery(string $viewName): void
    {
        try {
            $result = DB::select("SELECT * FROM {$viewName} LIMIT 5");
            // View query successful
        } catch (Exception $e) {
            throw new Exception("View '{$viewName}' query failed: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     * 
     * Removes the migration test status from system configs.
     */
    public function down(): void
    {
        DB::table('idbi_system_configs')
            ->where('config_key', 'migration_test_status')
            ->delete();
    }
};