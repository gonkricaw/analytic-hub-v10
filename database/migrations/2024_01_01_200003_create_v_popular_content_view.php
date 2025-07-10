<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the v_popular_content view for tracking most visited content
     * and content analytics in the Analytics Hub system.
     */
    public function up(): void
    {
        DB::statement("
            CREATE VIEW v_popular_content AS
            SELECT 
                c.id,
                c.title,
                c.slug,
                c.excerpt,
                c.type,
                c.category,
                c.status,
                c.published_at,
                c.featured_image,
                c.is_featured,
                
                -- Direct view count from content table
                c.view_count as total_views,
                
                -- Activity-based view tracking (last 30 days)
                COUNT(CASE 
                    WHEN ua.activity_type = 'content_view' 
                    AND ua.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    THEN 1 
                END) as views_last_30_days,
                
                -- Activity-based view tracking (last 7 days)
                COUNT(CASE 
                    WHEN ua.activity_type = 'content_view' 
                    AND ua.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    THEN 1 
                END) as views_last_7_days,
                
                -- Activity-based view tracking (today)
                COUNT(CASE 
                    WHEN ua.activity_type = 'content_view' 
                    AND DATE(ua.created_at) = CURDATE()
                    THEN 1 
                END) as views_today,
                
                -- Unique viewers (last 30 days)
                COUNT(DISTINCT CASE 
                    WHEN ua.activity_type = 'content_view' 
                    AND ua.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    AND ua.user_id IS NOT NULL
                    THEN ua.user_id 
                END) as unique_viewers_30_days,
                
                -- Unique viewers (last 7 days)
                COUNT(DISTINCT CASE 
                    WHEN ua.activity_type = 'content_view' 
                    AND ua.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    AND ua.user_id IS NOT NULL
                    THEN ua.user_id 
                END) as unique_viewers_7_days,
                
                -- Average views per day (last 30 days)
                ROUND(
                    COUNT(CASE 
                        WHEN ua.activity_type = 'content_view' 
                        AND ua.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                        THEN 1 
                    END) / 30.0, 2
                ) as avg_daily_views_30_days,
                
                -- First view date
                MIN(CASE 
                    WHEN ua.activity_type = 'content_view' 
                    THEN ua.created_at 
                END) as first_view_date,
                
                -- Last view date
                MAX(CASE 
                    WHEN ua.activity_type = 'content_view' 
                    THEN ua.created_at 
                END) as last_view_date,
                
                -- Days since last view
                CASE 
                    WHEN MAX(CASE WHEN ua.activity_type = 'content_view' THEN ua.created_at END) IS NOT NULL
                    THEN EXTRACT(DAY FROM (CURRENT_TIMESTAMP - MAX(CASE WHEN ua.activity_type = 'content_view' THEN ua.created_at END)))
                    ELSE NULL
                END as days_since_last_view,
                
                -- Peak viewing hour
                MODE() WITHIN GROUP (
                    ORDER BY EXTRACT(HOUR FROM 
                        CASE WHEN ua.activity_type = 'content_view' THEN ua.created_at END
                    )
                ) as peak_viewing_hour,
                
                -- Engagement score (weighted calculation)
                ROUND(
                    (COUNT(CASE 
                        WHEN ua.activity_type = 'content_view' 
                        AND ua.created_at >= CURRENT_DATE - INTERVAL '7 days'
                        THEN 1 
                    END) * 10) +
                    (COUNT(CASE 
                        WHEN ua.activity_type = 'content_view' 
                        AND ua.created_at >= CURRENT_DATE - INTERVAL '30 days'
                        AND ua.created_at < CURRENT_DATE - INTERVAL '7 days'
                        THEN 1 
                    END) * 5) +
                    (COUNT(CASE 
                        WHEN ua.activity_type = 'content_view' 
                        AND ua.created_at >= CURRENT_DATE - INTERVAL '90 days'
                        AND ua.created_at < CURRENT_DATE - INTERVAL '30 days'
                        THEN 1 
                    END) * 2) +
                    (c.view_count * 0.1), 2
                ) as engagement_score,
                
                -- Content age in days
                EXTRACT(DAY FROM (CURRENT_TIMESTAMP - c.published_at)) as content_age_days,
                
                -- Views per day since publication
                CASE 
                    WHEN c.published_at IS NOT NULL AND c.published_at <= CURRENT_TIMESTAMP
                    THEN ROUND(
                        c.view_count / GREATEST(
                            EXTRACT(DAY FROM (CURRENT_TIMESTAMP - c.published_at)), 1
                        ), 2
                    )
                    ELSE 0
                END as views_per_day_since_publication,
                
                -- Trending indicator (views increasing)
                CASE 
                    WHEN COUNT(CASE 
                        WHEN ua.activity_type = 'content_view' 
                        AND ua.created_at >= CURRENT_DATE - INTERVAL '7 days'
                        THEN 1 
                    END) > 
                    COUNT(CASE 
                        WHEN ua.activity_type = 'content_view' 
                        AND ua.created_at >= CURRENT_DATE - INTERVAL '14 days'
                        AND ua.created_at < CURRENT_DATE - INTERVAL '7 days'
                        THEN 1 
                    END)
                    THEN true
                    ELSE false
                END as is_trending,
                
                -- Growth rate (7-day vs previous 7-day)
                CASE 
                    WHEN COUNT(CASE 
                        WHEN ua.activity_type = 'content_view' 
                        AND ua.created_at >= CURRENT_DATE - INTERVAL '14 days'
                        AND ua.created_at < CURRENT_DATE - INTERVAL '7 days'
                        THEN 1 
                    END) > 0
                    THEN ROUND(
                        ((COUNT(CASE 
                            WHEN ua.activity_type = 'content_view' 
                            AND ua.created_at >= CURRENT_DATE - INTERVAL '7 days'
                            THEN 1 
                        END) - 
                        COUNT(CASE 
                            WHEN ua.activity_type = 'content_view' 
                            AND ua.created_at >= CURRENT_DATE - INTERVAL '14 days'
                            AND ua.created_at < CURRENT_DATE - INTERVAL '7 days'
                            THEN 1 
                        END)) * 100.0) / 
                        COUNT(CASE 
                            WHEN ua.activity_type = 'content_view' 
                            AND ua.created_at >= CURRENT_DATE - INTERVAL '14 days'
                            AND ua.created_at < CURRENT_DATE - INTERVAL '7 days'
                            THEN 1 
                        END), 2
                    )
                    ELSE NULL
                END as growth_rate_percentage,
                
                -- Device breakdown for views
                COUNT(CASE 
                    WHEN ua.activity_type = 'content_view' 
                    AND ua.properties->>'device_type' = 'mobile'
                    AND ua.created_at >= CURRENT_DATE - INTERVAL '30 days'
                    THEN 1 
                END) as mobile_views_30_days,
                
                COUNT(CASE 
                    WHEN ua.activity_type = 'content_view' 
                    AND ua.properties->>'device_type' = 'desktop'
                    AND ua.created_at >= CURRENT_DATE - INTERVAL '30 days'
                    THEN 1 
                END) as desktop_views_30_days,
                
                -- Return visitor rate
                CASE 
                    WHEN COUNT(DISTINCT CASE 
                        WHEN ua.activity_type = 'content_view' 
                        AND ua.created_at >= CURRENT_DATE - INTERVAL '30 days'
                        AND ua.user_id IS NOT NULL
                        THEN ua.user_id 
                    END) > 0
                    THEN ROUND(
                        (COUNT(CASE 
                            WHEN ua.activity_type = 'content_view' 
                            AND ua.created_at >= CURRENT_DATE - INTERVAL '30 days'
                            THEN 1 
                        END) * 100.0) / 
                        COUNT(DISTINCT CASE 
                            WHEN ua.activity_type = 'content_view' 
                            AND ua.created_at >= CURRENT_DATE - INTERVAL '30 days'
                            AND ua.user_id IS NOT NULL
                            THEN ua.user_id 
                        END), 2
                    )
                    ELSE 0
                END as return_visitor_rate
                
            FROM idbi_contents c
            LEFT JOIN idbi_user_activities ua ON (
                ua.subject_type = 'App\\Models\\Content' 
                AND ua.subject_id = c.id
                AND ua.activity_type = 'content_view'
            )
            WHERE c.status = 'published'
            AND c.deleted_at IS NULL
            AND (c.expires_at IS NULL OR c.expires_at > CURRENT_TIMESTAMP)
            GROUP BY 
                c.id, c.title, c.slug, c.excerpt, c.type, c.category, 
                c.status, c.published_at, c.featured_image, c.is_featured, c.view_count
            ORDER BY engagement_score DESC, views_last_30_days DESC, total_views DESC
        ");
    }

    /**
     * Reverse the migrations.
     * 
     * Drops the v_popular_content view.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_popular_content');
    }
};