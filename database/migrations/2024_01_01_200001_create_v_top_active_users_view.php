<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the v_top_active_users view for displaying monthly login statistics
     * and identifying the most active users in the Analytics Hub system.
     */
    public function up(): void
    {
        DB::statement("
            CREATE VIEW v_top_active_users AS
            SELECT 
                u.id,
                u.first_name,
                u.last_name,
                CONCAT(u.first_name, ' ', u.last_name) as full_name,
                u.email,
                u.department,
                u.position,
                u.status,
                u.last_login_at,
                
                -- Current month statistics
                COUNT(CASE 
                    WHEN la.status = 'success' 
                    AND la.attempted_at >= DATE_TRUNC('month', CURRENT_DATE)
                    AND la.attempted_at < DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month'
                    THEN 1 
                END) as current_month_logins,
                
                -- Previous month statistics
                COUNT(CASE 
                    WHEN la.status = 'success' 
                    AND la.attempted_at >= DATE_TRUNC('month', CURRENT_DATE) - INTERVAL '1 month'
                    AND la.attempted_at < DATE_TRUNC('month', CURRENT_DATE)
                    THEN 1 
                END) as previous_month_logins,
                
                -- Last 30 days statistics
                COUNT(CASE 
                    WHEN la.status = 'success' 
                    AND la.attempted_at >= CURRENT_DATE - INTERVAL '30 days'
                    THEN 1 
                END) as last_30_days_logins,
                
                -- Total successful logins
                COUNT(CASE 
                    WHEN la.status = 'success' 
                    THEN 1 
                END) as total_logins,
                
                -- Average logins per month (last 6 months)
                ROUND(
                    COUNT(CASE 
                        WHEN la.status = 'success' 
                        AND la.attempted_at >= CURRENT_DATE - INTERVAL '6 months'
                        THEN 1 
                    END) / 6.0, 2
                ) as avg_monthly_logins,
                
                -- First login date
                MIN(CASE 
                    WHEN la.status = 'success' 
                    THEN la.attempted_at 
                END) as first_login_date,
                
                -- Most recent successful login
                MAX(CASE 
                    WHEN la.status = 'success' 
                    THEN la.attempted_at 
                END) as last_successful_login,
                
                -- Days since last login
                CASE 
                    WHEN MAX(CASE WHEN la.status = 'success' THEN la.attempted_at END) IS NOT NULL
                    THEN EXTRACT(DAY FROM (CURRENT_TIMESTAMP - MAX(CASE WHEN la.status = 'success' THEN la.attempted_at END)))
                    ELSE NULL
                END as days_since_last_login,
                
                -- Activity score (weighted calculation)
                ROUND(
                    (COUNT(CASE 
                        WHEN la.status = 'success' 
                        AND la.attempted_at >= CURRENT_DATE - INTERVAL '7 days'
                        THEN 1 
                    END) * 10) +
                    (COUNT(CASE 
                        WHEN la.status = 'success' 
                        AND la.attempted_at >= CURRENT_DATE - INTERVAL '30 days'
                        AND la.attempted_at < CURRENT_DATE - INTERVAL '7 days'
                        THEN 1 
                    END) * 5) +
                    (COUNT(CASE 
                        WHEN la.status = 'success' 
                        AND la.attempted_at >= CURRENT_DATE - INTERVAL '90 days'
                        AND la.attempted_at < CURRENT_DATE - INTERVAL '30 days'
                        THEN 1 
                    END) * 2), 2
                ) as activity_score,
                
                -- Unique login days in current month
                COUNT(DISTINCT CASE 
                    WHEN la.status = 'success' 
                    AND la.attempted_at >= DATE_TRUNC('month', CURRENT_DATE)
                    AND la.attempted_at < DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month'
                    THEN DATE(la.attempted_at)
                END) as unique_login_days_current_month,
                
                -- Login consistency (percentage of days logged in this month)
                ROUND(
                    (COUNT(DISTINCT CASE 
                        WHEN la.status = 'success' 
                        AND la.attempted_at >= DATE_TRUNC('month', CURRENT_DATE)
                        AND la.attempted_at < DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month'
                        THEN DATE(la.attempted_at)
                    END) * 100.0) / 
                    EXTRACT(DAY FROM CURRENT_DATE), 2
                ) as login_consistency_percentage
                
            FROM idbi_users u
            LEFT JOIN idbi_login_attempts la ON u.id = la.user_id
            WHERE u.status IN ('active', 'suspended')
            AND u.deleted_at IS NULL
            GROUP BY 
                u.id, u.first_name, u.last_name, u.email, 
                u.department, u.position, u.status, u.last_login_at
            HAVING COUNT(CASE WHEN la.status = 'success' THEN 1 END) > 0
            ORDER BY activity_score DESC, current_month_logins DESC, last_successful_login DESC
        ");
    }

    /**
     * Reverse the migrations.
     * 
     * Drops the v_top_active_users view.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_top_active_users');
    }
};