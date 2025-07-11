<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the v_login_trends view for displaying 15-day login trend data
     * for dashboard charts and analytics in the Analytics Hub system.
     */
    public function up(): void
    {
        // Drop view if it exists first
        DB::statement('DROP VIEW IF EXISTS v_login_trends');
        
        // Check if we're using SQLite (for testing) or another database
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite-compatible version - simplified view without complex CTEs
            DB::statement("
                CREATE VIEW v_login_trends AS
                SELECT 
                    date(la.attempted_at) as login_date,
                    
                    -- Day information
                    strftime('%w', date(la.attempted_at)) as day_name,
                    strftime('%d/%m/%Y', date(la.attempted_at)) as formatted_date,
                    CAST(strftime('%w', date(la.attempted_at)) AS INTEGER) as day_of_week,
                    
                    -- Login statistics
                    COUNT(CASE WHEN la.status = 'success' THEN 1 END) as successful_logins,
                    COUNT(CASE WHEN la.status = 'failed' THEN 1 END) as failed_logins,
                    COUNT(CASE WHEN la.status = 'blocked' THEN 1 END) as blocked_logins,
                    COUNT(CASE WHEN la.status = 'suspicious' THEN 1 END) as suspicious_logins,
                    COUNT(*) as total_attempts,
                    
                    -- User statistics
                    COUNT(DISTINCT la.user_id) as unique_users_attempted,
                    COUNT(DISTINCT CASE WHEN la.status = 'success' THEN la.user_id END) as unique_users_success,
                    COUNT(DISTINCT la.ip_address) as unique_ip_addresses,
                    0 as first_time_logins, -- Simplified for SQLite
                    
                    -- Performance metrics
                    CAST(strftime('%H', la.attempted_at) AS INTEGER) as peak_hour,
                    ROUND(CAST(COUNT(*) AS REAL) / NULLIF(COUNT(DISTINCT la.user_id), 0), 2) as avg_attempts_per_user,
                    ROUND((COUNT(CASE WHEN la.status = 'success' THEN 1 END) * 100.0) / NULLIF(COUNT(*), 0), 2) as success_rate_percentage,
                    
                    -- Device breakdown
                    COUNT(CASE WHEN la.device_type = 'mobile' THEN 1 END) as mobile_logins,
                    COUNT(CASE WHEN la.device_type = 'desktop' THEN 1 END) as desktop_logins,
                    COUNT(CASE WHEN la.device_type = 'tablet' THEN 1 END) as tablet_logins,
                    
                    -- Geographic and security
                    COUNT(DISTINCT la.country) as unique_countries,
                    COUNT(CASE WHEN la.remember_me = 1 THEN 1 END) as remember_me_count,
                    COUNT(CASE WHEN la.risk_score >= 70 THEN 1 END) as high_risk_attempts,
                    ROUND(AVG(la.risk_score), 2) as avg_risk_score,
                    
                    -- Simplified trend indicators
                    0 as prev_day_successful,
                    0 as prev_day_total,
                    0 as growth_percentage,
                    
                    -- Weekend indicator
                    CASE WHEN CAST(strftime('%w', date(la.attempted_at)) AS INTEGER) IN (0, 6) THEN 1 ELSE 0 END as is_weekend,
                    
                    -- Days ago
                    CAST(julianday('now') - julianday(date(la.attempted_at)) AS INTEGER) as days_ago
                    
                FROM idbi_login_attempts la
                WHERE la.attempted_at >= date('now', '-14 days')
                AND la.attempted_at <= date('now', '+1 day')
                GROUP BY date(la.attempted_at)
                ORDER BY date(la.attempted_at) DESC
            ");
        } else {
            // MySQL/PostgreSQL version with full CTE support
            DB::statement("
                CREATE VIEW v_login_trends AS
                WITH RECURSIVE date_series AS (
                    SELECT DATE_SUB(CURDATE(), INTERVAL 14 DAY) as login_date
                    UNION ALL
                    SELECT DATE_ADD(login_date, INTERVAL 1 DAY)
                    FROM date_series
                    WHERE login_date < CURDATE()
                ),
                daily_stats AS (
                    SELECT 
                        DATE(la.attempted_at) as login_date,
                        
                        -- Successful logins
                        COUNT(CASE WHEN la.status = 'success' THEN 1 END) as successful_logins,
                        
                        -- Failed logins
                        COUNT(CASE WHEN la.status = 'failed' THEN 1 END) as failed_logins,
                        
                        -- Blocked logins
                        COUNT(CASE WHEN la.status = 'blocked' THEN 1 END) as blocked_logins,
                        
                        -- Suspicious logins
                        COUNT(CASE WHEN la.status = 'suspicious' THEN 1 END) as suspicious_logins,
                        
                        -- Total login attempts
                        COUNT(*) as total_attempts,
                        
                        -- Unique users who attempted login
                        COUNT(DISTINCT la.user_id) as unique_users_attempted,
                        
                        -- Unique users who successfully logged in
                        COUNT(DISTINCT CASE WHEN la.status = 'success' THEN la.user_id END) as unique_users_success,
                        
                        -- Unique IP addresses
                        COUNT(DISTINCT la.ip_address) as unique_ip_addresses,
                        
                        -- First time logins (users logging in for the first time)
                        COUNT(CASE 
                            WHEN la.status = 'success' 
                            AND NOT EXISTS (
                                SELECT 1 FROM idbi_login_attempts la2 
                                WHERE la2.user_id = la.user_id 
                                AND la2.status = 'success' 
                                AND DATE(la2.attempted_at) < DATE(la.attempted_at)
                            )
                            THEN 1 
                        END) as first_time_logins,
                        
                        -- Peak hour of the day (most common hour)
                        (
                            SELECT HOUR(attempted_at) 
                            FROM idbi_login_attempts la2 
                            WHERE DATE(la2.attempted_at) = DATE(la.attempted_at)
                            GROUP BY HOUR(attempted_at) 
                            ORDER BY COUNT(*) DESC 
                            LIMIT 1
                        ) as peak_hour,
                        
                        -- Average attempts per user
                        ROUND(
                            COUNT(*) / NULLIF(COUNT(DISTINCT la.user_id), 0), 2
                        ) as avg_attempts_per_user,
                        
                        -- Success rate percentage
                        ROUND(
                            (COUNT(CASE WHEN la.status = 'success' THEN 1 END) * 100.0) / 
                            NULLIF(COUNT(*), 0), 2
                        ) as success_rate_percentage,
                        
                        -- Mobile vs Desktop breakdown
                        COUNT(CASE WHEN la.device_type = 'mobile' THEN 1 END) as mobile_logins,
                        COUNT(CASE WHEN la.device_type = 'desktop' THEN 1 END) as desktop_logins,
                        COUNT(CASE WHEN la.device_type = 'tablet' THEN 1 END) as tablet_logins,
                        
                        -- Geographic diversity (unique countries)
                        COUNT(DISTINCT la.country) as unique_countries,
                        
                        -- Remember me usage
                        COUNT(CASE WHEN la.remember_me = true THEN 1 END) as remember_me_count,
                        
                        -- High risk attempts
                        COUNT(CASE WHEN la.risk_score >= 70 THEN 1 END) as high_risk_attempts,
                        
                        -- Average risk score
                        ROUND(AVG(la.risk_score), 2) as avg_risk_score
                        
                    FROM idbi_login_attempts la
                    WHERE la.attempted_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
                    AND la.attempted_at <= DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                    GROUP BY DATE(la.attempted_at)
                )
                SELECT 
                    ds.login_date,
                    
                    -- Day information
                    DAYNAME(ds.login_date) as day_name,
                    DATE_FORMAT(ds.login_date, '%d/%m/%Y') as formatted_date,
                    DAYOFWEEK(ds.login_date) - 1 as day_of_week, -- 0=Sunday, 6=Saturday
                    
                    -- Login statistics (with 0 defaults for days with no data)
                    COALESCE(dst.successful_logins, 0) as successful_logins,
                    COALESCE(dst.failed_logins, 0) as failed_logins,
                    COALESCE(dst.blocked_logins, 0) as blocked_logins,
                    COALESCE(dst.suspicious_logins, 0) as suspicious_logins,
                    COALESCE(dst.total_attempts, 0) as total_attempts,
                    
                    -- User statistics
                    COALESCE(dst.unique_users_attempted, 0) as unique_users_attempted,
                    COALESCE(dst.unique_users_success, 0) as unique_users_success,
                    COALESCE(dst.unique_ip_addresses, 0) as unique_ip_addresses,
                    COALESCE(dst.first_time_logins, 0) as first_time_logins,
                    
                    -- Performance metrics
                    dst.peak_hour,
                    COALESCE(dst.avg_attempts_per_user, 0) as avg_attempts_per_user,
                    COALESCE(dst.success_rate_percentage, 0) as success_rate_percentage,
                    
                    -- Device breakdown
                    COALESCE(dst.mobile_logins, 0) as mobile_logins,
                    COALESCE(dst.desktop_logins, 0) as desktop_logins,
                    COALESCE(dst.tablet_logins, 0) as tablet_logins,
                    
                    -- Geographic and security
                    COALESCE(dst.unique_countries, 0) as unique_countries,
                    COALESCE(dst.remember_me_count, 0) as remember_me_count,
                    COALESCE(dst.high_risk_attempts, 0) as high_risk_attempts,
                    COALESCE(dst.avg_risk_score, 0) as avg_risk_score,
                    
                    -- Trend indicators (compared to previous day)
                    LAG(COALESCE(dst.successful_logins, 0)) OVER (ORDER BY ds.login_date) as prev_day_successful,
                    LAG(COALESCE(dst.total_attempts, 0)) OVER (ORDER BY ds.login_date) as prev_day_total,
                    
                    -- Growth percentage
                    CASE 
                        WHEN LAG(COALESCE(dst.successful_logins, 0)) OVER (ORDER BY ds.login_date) > 0
                        THEN ROUND(
                            ((COALESCE(dst.successful_logins, 0) - LAG(COALESCE(dst.successful_logins, 0)) OVER (ORDER BY ds.login_date)) * 100.0) /
                            LAG(COALESCE(dst.successful_logins, 0)) OVER (ORDER BY ds.login_date), 2
                        )
                        ELSE NULL
                    END as growth_percentage,
                    
                    -- Weekend indicator
                    CASE 
                        WHEN EXTRACT(DOW FROM ds.login_date) IN (0, 6) THEN true 
                        ELSE false 
                    END as is_weekend,
                    
                    -- Days ago (0 = today, 1 = yesterday, etc.)
                    EXTRACT(DAY FROM (CURRENT_DATE - ds.login_date)) as days_ago
                    
                FROM date_series ds
                LEFT JOIN daily_stats dst ON ds.login_date = dst.login_date
                ORDER BY ds.login_date DESC
            ");
        }
    }

    /**
     * Reverse the migrations.
     * 
     * Drops the v_login_trends view.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_login_trends');
    }
};