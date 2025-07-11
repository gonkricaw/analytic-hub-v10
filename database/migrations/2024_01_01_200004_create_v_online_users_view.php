<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Create v_online_users view
 * Purpose: Creates a database view to track online users and their session information
 * Related Feature: Dashboard - Online Users Widget
 * Dependencies: idbi_users table, sessions table, idbi_user_activities table
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the v_online_users view with different implementations based on database driver.
     * The view provides real-time information about active user sessions.
     */
    public function up(): void
    {
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
                    NULL as device_type,
                    NULL as browser,
                    NULL as platform,
                    1 as session_active,
                    1 as is_authenticated,
                    
                    -- Timing information (simplified for SQLite)
                    datetime(s.last_activity, 'unixepoch') as last_activity_timestamp,
                    NULL as session_started_at,
                    NULL as session_expires_at,
                    NULL as last_seen_at,
                    
                    -- Calculate session duration (simplified)
                    NULL as session_duration_minutes,
                    
                    -- Calculate idle time (in minutes)
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
                    
                    -- Simplified fields for SQLite
                    NULL as country,
                    NULL as region,
                    NULL as city,
                    NULL as latitude,
                    NULL as longitude,
                    0 as is_suspicious,
                    0 as risk_score,
                    NULL as security_flags,
                    NULL as has_remember_token,
                    NULL as idle_timeout,
                    NULL as max_lifetime,
                    0 as recent_activity_count,
                    NULL as last_activity_type,
                    NULL as last_activity_description,
                    NULL as current_page_url,
                    ROW_NUMBER() OVER (ORDER BY s.last_activity DESC) as activity_rank,
                    1 as active_sessions_count,
                    NULL as estimated_timezone_offset,
                    NULL as device_fingerprint,
                    NULL as logged_in_with_remember_me,
                    'good' as session_quality,
                    
                    -- Productivity indicator (default)
                    'low' as productivity_level
                    
                FROM sessions s
                INNER JOIN idbi_users u ON s.user_id::text = u.id::text
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
        } elseif ($driver === 'pgsql') {
            // PostgreSQL version with PostgreSQL-specific functions
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
                    NULL as device_type,
                    NULL as browser,
                    NULL as platform,
                    true as session_active,
                    true as is_authenticated,
                    
                    -- Timing information
                    TO_TIMESTAMP(s.last_activity) as last_activity_timestamp,
                    NULL as session_started_at,
                    NULL as session_expires_at,
                    NULL as last_seen_at,
                    
                    -- Calculate session duration
                    NULL as session_duration_minutes,
                    
                    -- Calculate idle time
                    EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - TO_TIMESTAMP(s.last_activity))) / 60 as idle_minutes,
                    
                    -- Online status indicators
                    CASE 
                        WHEN EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - TO_TIMESTAMP(s.last_activity))) <= 300
                        THEN 'online'
                        WHEN EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - TO_TIMESTAMP(s.last_activity))) <= 900
                        THEN 'away'
                        WHEN EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - TO_TIMESTAMP(s.last_activity))) <= 1800
                        THEN 'idle'
                        ELSE 'offline'
                    END as online_status,
                    
                    -- Activity level
                    CASE 
                        WHEN EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - TO_TIMESTAMP(s.last_activity))) <= 60
                        THEN 'very_active'
                        WHEN EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - TO_TIMESTAMP(s.last_activity))) <= 300
                        THEN 'active'
                        WHEN EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - TO_TIMESTAMP(s.last_activity))) <= 900
                        THEN 'moderate'
                        ELSE 'low'
                    END as activity_level,
                    
                    -- Simplified fields for PostgreSQL
                    NULL as country,
                    NULL as region,
                    NULL as city,
                    NULL as latitude,
                    NULL as longitude,
                    false as is_suspicious,
                    0 as risk_score,
                    NULL as security_flags,
                    NULL as has_remember_token,
                    NULL as idle_timeout,
                    NULL as max_lifetime,
                    0 as recent_activity_count,
                    NULL as last_activity_type,
                    NULL as last_activity_description,
                    NULL as current_page_url,
                    ROW_NUMBER() OVER (ORDER BY TO_TIMESTAMP(s.last_activity) DESC) as activity_rank,
                    1 as active_sessions_count,
                    NULL as estimated_timezone_offset,
                    MD5(COALESCE(s.user_agent, '')) as device_fingerprint,
                    NULL as logged_in_with_remember_me,
                    'good' as session_quality,
                    'low' as productivity_level
                    
                FROM sessions s
                INNER JOIN idbi_users u ON s.user_id::text = u.id::text
                WHERE u.status = 'active'
                AND u.deleted_at IS NULL
                -- Consider sessions active if last activity was within 30 minutes
                AND EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - TO_TIMESTAMP(s.last_activity))) <= 1800
                ORDER BY 
                    CASE 
                        WHEN EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - TO_TIMESTAMP(s.last_activity))) <= 300 THEN 1 -- online
                        WHEN EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - TO_TIMESTAMP(s.last_activity))) <= 900 THEN 2 -- away
                        ELSE 3 -- idle
                    END,
                    TO_TIMESTAMP(s.last_activity) DESC
            ");
        } else {
            // MySQL version with MySQL-specific functions
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
                    NULL as device_type,
                    NULL as browser,
                    NULL as platform,
                    true as session_active,
                    true as is_authenticated,
                    
                    -- Timing information
                    FROM_UNIXTIME(s.last_activity) as last_activity_timestamp,
                    NULL as session_started_at,
                    NULL as session_expires_at,
                    NULL as last_seen_at,
                    
                    -- Calculate session duration
                    NULL as session_duration_minutes,
                    
                    -- Calculate idle time
                    TIMESTAMPDIFF(MINUTE, FROM_UNIXTIME(s.last_activity), NOW()) as idle_minutes,
                    
                    -- Online status indicators
                    CASE 
                        WHEN TIMESTAMPDIFF(SECOND, FROM_UNIXTIME(s.last_activity), NOW()) <= 300
                        THEN 'online'
                        WHEN TIMESTAMPDIFF(SECOND, FROM_UNIXTIME(s.last_activity), NOW()) <= 900
                        THEN 'away'
                        WHEN TIMESTAMPDIFF(SECOND, FROM_UNIXTIME(s.last_activity), NOW()) <= 1800
                        THEN 'idle'
                        ELSE 'offline'
                    END as online_status,
                    
                    -- Activity level
                    CASE 
                        WHEN TIMESTAMPDIFF(SECOND, FROM_UNIXTIME(s.last_activity), NOW()) <= 60
                        THEN 'very_active'
                        WHEN TIMESTAMPDIFF(SECOND, FROM_UNIXTIME(s.last_activity), NOW()) <= 300
                        THEN 'active'
                        WHEN TIMESTAMPDIFF(SECOND, FROM_UNIXTIME(s.last_activity), NOW()) <= 900
                        THEN 'moderate'
                        ELSE 'low'
                    END as activity_level,
                    
                    -- Simplified fields for MySQL
                    NULL as country,
                    NULL as region,
                    NULL as city,
                    NULL as latitude,
                    NULL as longitude,
                    false as is_suspicious,
                    0 as risk_score,
                    NULL as security_flags,
                    NULL as has_remember_token,
                    NULL as idle_timeout,
                    NULL as max_lifetime,
                    0 as recent_activity_count,
                    NULL as last_activity_type,
                    NULL as last_activity_description,
                    NULL as current_page_url,
                    ROW_NUMBER() OVER (ORDER BY s.last_activity DESC) as activity_rank,
                    1 as active_sessions_count,
                    NULL as estimated_timezone_offset,
                    MD5(COALESCE(s.user_agent, '')) as device_fingerprint,
                    NULL as logged_in_with_remember_me,
                    'good' as session_quality,
                    'low' as productivity_level
                    
                FROM sessions s
                INNER JOIN idbi_users u ON s.user_id = u.id
                WHERE u.status = 'active'
                AND u.deleted_at IS NULL
                AND TIMESTAMPDIFF(SECOND, FROM_UNIXTIME(s.last_activity), NOW()) <= 1800
                ORDER BY s.last_activity DESC
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