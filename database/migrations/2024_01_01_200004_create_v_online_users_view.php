<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the v_online_users view for tracking real-time active sessions
     * and online user statistics in the Analytics Hub system.
     */
    public function up(): void
    {
        // Drop view if it exists first
        DB::statement('DROP VIEW IF EXISTS v_online_users');
        
        // Check if we're using SQLite (for testing) or another database
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite-compatible version with simplified calculations
            // Note: Using only columns that exist in the basic sessions table
            DB::statement("
                CREATE VIEW v_online_users AS
                SELECT 
                    u.id as user_id,
                    u.first_name,
                    u.last_name,
                    (u.first_name || ' ' || u.last_name) as full_name,
                    u.email,
                    u.department,
                    u.position,
                    u.status as user_status,
                    
                    -- Session information (only existing columns)
                    s.id as session_id,
                    s.ip_address,
                    s.user_agent,
                    'unknown' as device_type,
                    'unknown' as browser,
                    'unknown' as platform,
                    1 as session_active,
                    1 as is_authenticated,
                    
                    -- Timing information
                    datetime(s.last_activity, 'unixepoch') as last_activity_timestamp,
                    datetime('now') as session_started_at,
                    datetime('now', '+2 hours') as session_expires_at,
                    datetime(s.last_activity, 'unixepoch') as last_seen_at,
                    
                    -- Calculate session duration (simplified)
                    CAST((julianday('now') - julianday(datetime(s.last_activity, 'unixepoch'))) * 1440 AS INTEGER) as session_duration_minutes,
                    
                    -- Calculate idle time
                    CAST((julianday('now') - julianday(datetime(s.last_activity, 'unixepoch'))) * 1440 AS INTEGER) as idle_minutes,
                    
                    -- Online status indicators
                    CASE 
                        WHEN (julianday('now') - julianday(datetime(s.last_activity, 'unixepoch'))) * 86400 <= 300 -- 5 minutes
                        THEN 'online'
                        WHEN (julianday('now') - julianday(datetime(s.last_activity, 'unixepoch'))) * 86400 <= 900 -- 15 minutes
                        THEN 'away'
                        WHEN (julianday('now') - julianday(datetime(s.last_activity, 'unixepoch'))) * 86400 <= 1800 -- 30 minutes
                        THEN 'idle'
                        ELSE 'offline'
                    END as online_status,
                    
                    -- Activity level
                    CASE 
                        WHEN (julianday('now') - julianday(datetime(s.last_activity, 'unixepoch'))) * 86400 <= 60 -- 1 minute
                        THEN 'very_active'
                        WHEN (julianday('now') - julianday(datetime(s.last_activity, 'unixepoch'))) * 86400 <= 300 -- 5 minutes
                        THEN 'active'
                        WHEN (julianday('now') - julianday(datetime(s.last_activity, 'unixepoch'))) * 86400 <= 900 -- 15 minutes
                        THEN 'moderate'
                        ELSE 'low'
                    END as activity_level,
                    
                    -- Geographic information (defaults)
                    'unknown' as country,
                    'unknown' as region,
                    'unknown' as city,
                    NULL as latitude,
                    NULL as longitude,
                    
                    -- Security information (defaults)
                    0 as is_suspicious,
                    0 as risk_score,
                    '{}' as security_flags,
                    NULL as has_remember_token,
                    
                    -- Session metadata (defaults)
                    1800 as idle_timeout,
                    7200 as max_lifetime,
                    
                    -- Recent activity count (simplified)
                    0 as recent_activity_count,
                    
                    -- Activity information (defaults)
                    'session' as last_activity_type,
                    'User session activity' as last_activity_description,
                    NULL as current_page_url,
                    
                    -- Session rank by activity
                    ROW_NUMBER() OVER (
                        ORDER BY datetime(s.last_activity, 'unixepoch') DESC
                    ) as activity_rank,
                    
                    -- Multiple sessions indicator
                    (
                        SELECT COUNT(*)
                        FROM sessions s2
                        WHERE s2.user_id = u.id
                        AND (julianday('now') - julianday(datetime(s2.last_activity, 'unixepoch'))) * 86400 <= 1800 -- 30 minutes
                    ) as active_sessions_count,
                    
                    -- Time zone estimation (default)
                    0 as estimated_timezone_offset,
                    
                    -- Device fingerprint (simplified)
                    lower(hex(randomblob(16))) as device_fingerprint,
                    
                    -- Login method tracking (default)
                    0 as logged_in_with_remember_me,
                    
                    -- Session quality score (default)
                    'good' as session_quality,
                    
                    -- Productivity indicator (default)
                    'low' as productivity_level
                    
                FROM sessions s
                INNER JOIN idbi_users u ON s.user_id = u.id
                WHERE u.status = 'active'
                AND u.deleted_at IS NULL
                -- Consider sessions active if last activity was within 30 minutes
                AND (julianday('now') - julianday(datetime(s.last_activity, 'unixepoch'))) * 86400 <= 1800
                ORDER BY 
                    CASE 
                        WHEN (julianday('now') - julianday(datetime(s.last_activity, 'unixepoch'))) * 86400 <= 300 THEN 1 -- online
                        WHEN (julianday('now') - julianday(datetime(s.last_activity, 'unixepoch'))) * 86400 <= 900 THEN 2 -- away
                        ELSE 3 -- idle
                    END,
                    datetime(s.last_activity, 'unixepoch') DESC
            ");
        } else {
            // MySQL/PostgreSQL version with full feature support
            DB::statement("
                CREATE VIEW v_online_users AS
                SELECT 
                    u.id as user_id,
                    u.first_name,
                    u.last_name,
                    CONCAT(u.first_name, ' ', u.last_name) as full_name,
                    u.email,
                    u.department,
                    u.position,
                    u.status as user_status,
                    
                    -- Session information
                    s.id as session_id,
                    s.ip_address,
                    s.user_agent,
                    s.device_type,
                    s.browser,
                    s.platform,
                    s.is_active as session_active,
                    s.is_authenticated,
                    
                    -- Timing information
                    FROM_UNIXTIME(s.last_activity) as last_activity_timestamp,
                    s.created_at as session_started_at,
                    s.expires_at as session_expires_at,
                    s.last_seen_at,
                    
                    -- Calculate session duration
                    TIMESTAMPDIFF(MINUTE, s.created_at, FROM_UNIXTIME(s.last_activity)) as session_duration_minutes,
                    
                    -- Calculate idle time
                    TIMESTAMPDIFF(MINUTE, FROM_UNIXTIME(s.last_activity), NOW()) as idle_minutes,
                    
                    -- Online status indicators
                    CASE 
                        WHEN TIMESTAMPDIFF(SECOND, FROM_UNIXTIME(s.last_activity), NOW()) <= 300 -- 5 minutes
                        THEN 'online'
                        WHEN TIMESTAMPDIFF(SECOND, FROM_UNIXTIME(s.last_activity), NOW()) <= 900 -- 15 minutes
                        THEN 'away'
                        WHEN TIMESTAMPDIFF(SECOND, FROM_UNIXTIME(s.last_activity), NOW()) <= 1800 -- 30 minutes
                        THEN 'idle'
                        ELSE 'offline'
                    END as online_status,
                    
                    -- Activity level
                    CASE 
                        WHEN TIMESTAMPDIFF(SECOND, FROM_UNIXTIME(s.last_activity), NOW()) <= 60 -- 1 minute
                        THEN 'very_active'
                        WHEN TIMESTAMPDIFF(SECOND, FROM_UNIXTIME(s.last_activity), NOW()) <= 300 -- 5 minutes
                        THEN 'active'
                        WHEN TIMESTAMPDIFF(SECOND, FROM_UNIXTIME(s.last_activity), NOW()) <= 900 -- 15 minutes
                        THEN 'moderate'
                        ELSE 'low'
                    END as activity_level,
                    
                    -- Geographic information
                    s.country,
                    s.region,
                    s.city,
                    s.latitude,
                    s.longitude,
                    
                    -- Security information
                    s.is_suspicious,
                    s.risk_score,
                    s.security_flags,
                    s.remember_token as has_remember_token,
                    
                    -- Session metadata
                    s.idle_timeout,
                    s.max_lifetime,
                    
                    -- Recent activity count (last hour)
                    (
                        SELECT COUNT(*)
                        FROM idbi_user_activities ua
                        WHERE ua.user_id = u.id
                        AND ua.session_id = s.id
                        AND ua.created_at >= CURRENT_TIMESTAMP - INTERVAL '1 hour'
                    ) as recent_activity_count,
                    
                    -- Last activity type
                    (
                        SELECT ua.activity_type
                        FROM idbi_user_activities ua
                        WHERE ua.user_id = u.id
                        AND ua.session_id = s.id
                        ORDER BY ua.created_at DESC
                        LIMIT 1
                    ) as last_activity_type,
                    
                    -- Last activity description
                    (
                        SELECT ua.description
                        FROM idbi_user_activities ua
                        WHERE ua.user_id = u.id
                        AND ua.session_id = s.id
                        ORDER BY ua.created_at DESC
                        LIMIT 1
                    ) as last_activity_description,
                    
                    -- Current page/URL
                    (
                        SELECT ua.url
                        FROM idbi_user_activities ua
                        WHERE ua.user_id = u.id
                        AND ua.session_id = s.id
                        AND ua.url IS NOT NULL
                        ORDER BY ua.created_at DESC
                        LIMIT 1
                    ) as current_page_url,
                    
                    -- Session rank by activity
                    ROW_NUMBER() OVER (
                        ORDER BY TO_TIMESTAMP(s.last_activity) DESC
                    ) as activity_rank,
                    
                    -- Multiple sessions indicator
                    (
                        SELECT COUNT(*)
                        FROM sessions s2
                        WHERE s2.user_id = u.id
                        AND s2.is_active = true
                        AND s2.is_authenticated = true
                        AND EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - TO_TIMESTAMP(s2.last_activity))) <= 1800 -- 30 minutes
                    ) as active_sessions_count,
                    
                    -- Time zone estimation (based on activity patterns)
                    CASE 
                        WHEN s.longitude IS NOT NULL
                        THEN ROUND(s.longitude / 15.0) -- Rough timezone estimation
                        ELSE NULL
                    END as estimated_timezone_offset,
                    
                    -- Device fingerprint (simplified)
                    MD5(CONCAT(
                        COALESCE(s.user_agent, ''),
                        COALESCE(s.device_type, ''),
                        COALESCE(s.browser, ''),
                        COALESCE(s.platform, '')
                    )) as device_fingerprint,
                    
                    -- Login method tracking
                    (
                        SELECT la.remember_me
                        FROM idbi_login_attempts la
                        WHERE la.user_id = u.id
                        AND la.status = 'success'
                        AND la.session_id = s.id
                        ORDER BY la.attempted_at DESC
                        LIMIT 1
                    ) as logged_in_with_remember_me,
                    
                    -- Session quality score
                    CASE 
                        WHEN s.is_suspicious = true OR s.risk_score >= 70 THEN 'poor'
                        WHEN s.risk_score >= 40 THEN 'fair'
                        WHEN s.risk_score >= 20 THEN 'good'
                        ELSE 'excellent'
                    END as session_quality,
                    
                    -- Productivity indicator (based on activity frequency)
                    CASE 
                        WHEN (
                            SELECT COUNT(*)
                            FROM idbi_user_activities ua
                            WHERE ua.user_id = u.id
                            AND ua.session_id = s.id
                            AND ua.created_at >= CURRENT_TIMESTAMP - INTERVAL '1 hour'
                        ) >= 10 THEN 'high'
                        WHEN (
                            SELECT COUNT(*)
                            FROM idbi_user_activities ua
                            WHERE ua.user_id = u.id
                            AND ua.session_id = s.id
                            AND ua.created_at >= CURRENT_TIMESTAMP - INTERVAL '1 hour'
                        ) >= 5 THEN 'medium'
                        ELSE 'low'
                    END as productivity_level
                    
                FROM sessions s
                INNER JOIN idbi_users u ON s.user_id = u.id
                WHERE s.is_active = true
                AND s.is_authenticated = true
                AND u.status = 'active'
                AND u.deleted_at IS NULL
                -- Consider sessions active if last activity was within 30 minutes
                AND EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - TO_TIMESTAMP(s.last_activity))) <= 1800
                -- Exclude expired sessions
                AND (s.expires_at IS NULL OR s.expires_at > CURRENT_TIMESTAMP)
                ORDER BY 
                    CASE 
                        WHEN EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - TO_TIMESTAMP(s.last_activity))) <= 300 THEN 1 -- online
                        WHEN EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - TO_TIMESTAMP(s.last_activity))) <= 900 THEN 2 -- away
                        ELSE 3 -- idle
                    END,
                    TO_TIMESTAMP(s.last_activity) DESC
            ");
        }
    }

    /**
     * Reverse the migrations.
     * 
     * Drops the v_online_users view.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_online_users');
    }
};