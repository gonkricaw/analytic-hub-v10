<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds optimized indexes for database views to improve query performance
     * in the Analytics Hub system.
     */
    public function up(): void
    {
        // Indexes for v_top_active_users view optimization
        Schema::table('idbi_login_attempts', function (Blueprint $table) {
            // Composite index for monthly login calculations
            $table->index(['user_id', 'status', 'attempted_at'], 'idx_login_attempts_user_status_date');
            
            // Index for date range queries
            $table->index(['attempted_at', 'status'], 'idx_login_attempts_date_status');
            
            // Index for successful login tracking
            $table->index(['status', 'attempted_at', 'user_id'], 'idx_login_attempts_success_tracking');
        });

        Schema::table('idbi_users', function (Blueprint $table) {
            // Index for active users filtering
            $table->index(['status', 'deleted_at'], 'idx_users_status_deleted');
            
            // Index for user activity correlation
            $table->index(['id', 'status', 'deleted_at'], 'idx_users_active_lookup');
        });

        // Indexes for v_login_trends view optimization
        Schema::table('idbi_login_attempts', function (Blueprint $table) {
            // Composite index for trend analysis
            $table->index(['attempted_at', 'status', 'ip_address'], 'idx_login_trends_analysis');
            
            // Index for device and browser analytics
            $table->index(['attempted_at', 'device_type', 'browser'], 'idx_login_device_analytics');
            
            // Index for geographic analysis
            $table->index(['attempted_at', 'country', 'region'], 'idx_login_geographic_analysis');
            
            // Index for security analysis
            $table->index(['attempted_at', 'is_suspicious', 'risk_score'], 'idx_login_security_analysis');
            
            // Index for remember me tracking
            $table->index(['attempted_at', 'remember_me'], 'idx_login_remember_tracking');
        });

        // Indexes for v_popular_content view optimization
        Schema::table('idbi_contents', function (Blueprint $table) {
            // Index for content status and visibility
            $table->index(['status', 'deleted_at', 'published_at'], 'idx_contents_published_status');
            
            // Index for content type filtering
            $table->index(['type', 'status', 'deleted_at'], 'idx_contents_type_status');
            
            // Index for featured content
            $table->index(['is_featured', 'status', 'published_at'], 'idx_contents_featured');
        });

        Schema::table('idbi_user_activities', function (Blueprint $table) {
            // Composite index for content view tracking
            $table->index(['subject_type', 'subject_id', 'created_at'], 'idx_activities_content_views');
            
            // Index for activity type filtering
            $table->index(['activity_type', 'created_at', 'subject_id'], 'idx_activities_type_date');
            
            // Index for user activity correlation
            $table->index(['user_id', 'created_at', 'activity_type'], 'idx_activities_user_tracking');
            
            // Index for content engagement analysis
            $table->index(['subject_id', 'activity_type', 'created_at'], 'idx_activities_engagement');
        });

        // Indexes for v_online_users view optimization
        Schema::table('sessions', function (Blueprint $table) {
            // Composite index for active session tracking
            $table->index(['user_id', 'last_activity'], 'idx_sessions_user_activity');
            
            // Index for session activity analysis
            $table->index(['last_activity', 'user_id'], 'idx_sessions_activity_user');
            
            // Index for IP-based session tracking
            $table->index(['ip_address', 'last_activity'], 'idx_sessions_ip_activity');
            
            // Index for user agent analysis
            $table->index(['user_agent', 'last_activity'], 'idx_sessions_user_agent');
        });

        Schema::table('idbi_user_activities', function (Blueprint $table) {
            // Index for session-based activity tracking
            $table->index(['session_id', 'created_at', 'activity_type'], 'idx_activities_session_tracking');
            
            // Index for recent activity queries
            $table->index(['user_id', 'session_id', 'created_at'], 'idx_activities_recent_user');
            
            // Index for URL tracking
            $table->index(['session_id', 'url', 'created_at'], 'idx_activities_url_tracking');
        });

        // Additional performance indexes for common queries
        Schema::table('idbi_users', function (Blueprint $table) {
            // Index for email lookups
            $table->index(['email', 'status'], 'idx_users_email_status');
            
            // Index for department analytics
            $table->index(['department', 'status', 'deleted_at'], 'idx_users_department');
            
            // Index for last login tracking
            $table->index(['last_login_at', 'status'], 'idx_users_last_login');
        });

        Schema::table('idbi_contents', function (Blueprint $table) {
            // Index for slug lookups
            $table->index(['slug', 'status'], 'idx_contents_slug_status');
            
            // Index for category filtering
            $table->index(['category', 'status', 'published_at'], 'idx_contents_category');
            
            // Index for view count sorting
            $table->index(['view_count', 'status'], 'idx_contents_view_count');
        });

        // Partial indexes for better performance (PostgreSQL specific)
        if (config('database.default') === 'pgsql') {
            // Create partial indexes for active records only
            DB::statement("
                CREATE INDEX idx_users_active_partial 
                ON idbi_users (id, email, status) 
                WHERE deleted_at IS NULL AND status = 'active'
            ");
            
            DB::statement("
                CREATE INDEX idx_contents_published_partial 
                ON idbi_contents (id, slug, view_count) 
                WHERE deleted_at IS NULL AND status = 'published'
            ");
            
            DB::statement("
                CREATE INDEX idx_sessions_online_partial 
                ON sessions (user_id, last_activity, ip_address) 
                WHERE user_id IS NOT NULL
            ");
            
            DB::statement("
                CREATE INDEX idx_login_attempts_recent_partial 
                ON idbi_login_attempts (user_id, attempted_at, status) 
                WHERE status IN ('success', 'failed')
            ");
        }
    }

    /**
     * Reverse the migrations.
     * 
     * Removes the indexes added for view optimization.
     */
    public function down(): void
    {
        // Drop indexes from idbi_login_attempts
        Schema::table('idbi_login_attempts', function (Blueprint $table) {
            $table->dropIndex('idx_login_attempts_user_status_date');
            $table->dropIndex('idx_login_attempts_date_status');
            $table->dropIndex('idx_login_attempts_success_tracking');
            $table->dropIndex('idx_login_trends_analysis');
            $table->dropIndex('idx_login_device_analytics');
            $table->dropIndex('idx_login_geographic_analysis');
            $table->dropIndex('idx_login_security_analysis');
            $table->dropIndex('idx_login_remember_tracking');
        });

        // Drop indexes from idbi_users
        Schema::table('idbi_users', function (Blueprint $table) {
            $table->dropIndex('idx_users_status_deleted');
            $table->dropIndex('idx_users_active_lookup');
            $table->dropIndex('idx_users_email_status');
            $table->dropIndex('idx_users_department');
            $table->dropIndex('idx_users_last_login');
        });

        // Drop indexes from idbi_contents
        Schema::table('idbi_contents', function (Blueprint $table) {
            $table->dropIndex('idx_contents_published_status');
            $table->dropIndex('idx_contents_type_status');
            $table->dropIndex('idx_contents_featured');
            $table->dropIndex('idx_contents_slug_status');
            $table->dropIndex('idx_contents_category');
            $table->dropIndex('idx_contents_view_count');
        });

        // Drop indexes from idbi_user_activities
        Schema::table('idbi_user_activities', function (Blueprint $table) {
            $table->dropIndex('idx_activities_content_views');
            $table->dropIndex('idx_activities_type_date');
            $table->dropIndex('idx_activities_user_tracking');
            $table->dropIndex('idx_activities_engagement');
            $table->dropIndex('idx_activities_session_tracking');
            $table->dropIndex('idx_activities_recent_user');
            $table->dropIndex('idx_activities_url_tracking');
        });

        // Drop indexes from sessions
            Schema::table('sessions', function (Blueprint $table) {
            $table->dropIndex('idx_sessions_user_activity');
            $table->dropIndex('idx_sessions_activity_user');
            $table->dropIndex('idx_sessions_ip_activity');
            $table->dropIndex('idx_sessions_user_agent');
        });

        // Drop partial indexes if PostgreSQL
        if (config('database.default') === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS idx_users_active_partial');
            DB::statement('DROP INDEX IF EXISTS idx_contents_published_partial');
            DB::statement('DROP INDEX IF EXISTS idx_sessions_online_partial');
            DB::statement('DROP INDEX IF EXISTS idx_login_attempts_recent_partial');
        }
    }
};