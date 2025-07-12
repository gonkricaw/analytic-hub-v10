<?php

namespace App\Services;

use App\Models\Content;
use App\Models\ContentAccessLog;
use App\Models\UserActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * ContentVisitTracker Service
 * 
 * Comprehensive content visit tracking and analytics service.
 * Handles visit logging, analytics generation, and performance metrics.
 * 
 * Features:
 * - Real-time visit tracking
 * - User behavior analytics
 * - Content performance metrics
 * - Geographic and device tracking
 * - Reading time analysis
 * - Return visitor tracking
 * - Popular content identification
 * 
 * @package App\Services
 * @author Analytics Hub Team
 * @version 1.0
 * @since 2024-01-01
 */
class ContentVisitTracker
{
    /**
     * Track a content visit with comprehensive analytics.
     * 
     * Records visit details including user behavior, device info,
     * and performance metrics for analytics purposes.
     * 
     * @param Content $content Content being visited
     * @param Request $request HTTP request instance
     * @param array $options Additional tracking options
     * @return ContentAccessLog Created access log entry
     */
    public function trackVisit(Content $content, Request $request, array $options = []): ContentAccessLog
    {
        $user = Auth::user();
        $startTime = microtime(true);
        
        try {
            // Create comprehensive access log
            $accessLog = ContentAccessLog::logAccess(
                $content->id,
                $options['access_type'] ?? ContentAccessLog::ACCESS_TYPE_VIEW,
                ContentAccessLog::RESULT_SUCCESS,
                array_merge([
                    'content_uuid' => $content->uuid,
                    'content_type' => $content->type,
                    'content_title' => $content->title,
                    'session_id' => session()->getId(),
                    'referer' => $request->header('referer'),
                    'country_code' => $this->getCountryCode($request->ip()),
                    'city' => $this->getCity($request->ip()),
                    'response_time_ms' => round((microtime(true) - $startTime) * 1000),
                    'metadata' => [
                        'content_category' => $content->category,
                        'content_status' => $content->status,
                        'is_featured' => $content->is_featured,
                        'reading_time_estimate' => $this->estimateReadingTime($content),
                        'visit_timestamp' => now()->toISOString(),
                    ]
                ], $options)
            );
            
            // Update content statistics
            $this->updateContentStats($content, $user);
            
            // Track user activity
            $this->trackUserActivity($content, $user, $request);
            
            // Update real-time analytics
            $this->updateRealTimeAnalytics($content, $user);
            
            // Log successful tracking
            Log::info('Content visit tracked', [
                'content_id' => $content->id,
                'user_id' => $user?->id,
                'access_log_id' => $accessLog->id,
                'ip_address' => $request->ip()
            ]);
            
            return $accessLog;
            
        } catch (\Exception $e) {
            Log::error('Content visit tracking failed', [
                'content_id' => $content->id,
                'user_id' => $user?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Track reading progress for content analytics.
     * 
     * Records user reading behavior including scroll depth,
     * time spent, and engagement metrics.
     * 
     * @param Content $content Content being read
     * @param array $progressData Reading progress data
     * @return void
     */
    public function trackReadingProgress(Content $content, array $progressData): void
    {
        try {
            $user = Auth::user();
            $sessionId = session()->getId();
            
            // Find existing access log for this session
            $accessLog = ContentAccessLog::where('content_id', $content->id)
                ->where('session_id', $sessionId)
                ->where('access_type', ContentAccessLog::ACCESS_TYPE_VIEW)
                ->where('created_at', '>=', now()->subHours(2))
                ->latest()
                ->first();
                
            if ($accessLog) {
                // Update reading progress
                $metadata = $accessLog->metadata ?? [];
                $metadata['reading_progress'] = array_merge(
                    $metadata['reading_progress'] ?? [],
                    [
                        'scroll_depth' => $progressData['scroll_depth'] ?? 0,
                        'time_spent_seconds' => $progressData['time_spent'] ?? 0,
                        'reading_speed_wpm' => $progressData['reading_speed'] ?? null,
                        'engagement_events' => $progressData['engagement_events'] ?? [],
                        'last_updated' => now()->toISOString()
                    ]
                );
                
                $accessLog->update([
                    'metadata' => $metadata,
                    'session_duration' => $progressData['time_spent'] ?? $accessLog->session_duration
                ]);
            }
            
            // Update user activity with reading progress
            UserActivity::create([
                'user_id' => $user?->id,
                'activity_type' => 'content_reading_progress',
                'subject_type' => Content::class,
                'subject_id' => $content->id,
                'description' => "Reading progress updated for content: {$content->title}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'properties' => $progressData
            ]);
            
        } catch (\Exception $e) {
            Log::error('Reading progress tracking failed', [
                'content_id' => $content->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get content visit analytics for a specific content.
     * 
     * Returns comprehensive analytics including views, unique visitors,
     * engagement metrics, and performance data.
     * 
     * @param Content $content Content to analyze
     * @param array $options Analytics options (date range, metrics)
     * @return array Analytics data
     */
    public function getContentAnalytics(Content $content, array $options = []): array
    {
        $dateRange = $this->getDateRange($options);
        $cacheKey = "content_analytics_{$content->id}_" . md5(serialize($options));
        
        return Cache::remember($cacheKey, 300, function () use ($content, $dateRange) {
            return [
                'basic_stats' => $this->getBasicStats($content, $dateRange),
                'visitor_analytics' => $this->getVisitorAnalytics($content, $dateRange),
                'engagement_metrics' => $this->getEngagementMetrics($content, $dateRange),
                'device_breakdown' => $this->getDeviceBreakdown($content, $dateRange),
                'geographic_data' => $this->getGeographicData($content, $dateRange),
                'time_analytics' => $this->getTimeAnalytics($content, $dateRange),
                'referrer_data' => $this->getReferrerData($content, $dateRange),
                'performance_metrics' => $this->getPerformanceMetrics($content, $dateRange)
            ];
        });
    }
    
    /**
     * Get popular content based on visit analytics.
     * 
     * Returns content ranked by various popularity metrics
     * including views, engagement, and trending indicators.
     * 
     * @param array $options Filtering and sorting options
     * @return \Illuminate\Support\Collection Popular content collection
     */
    public function getPopularContent(array $options = [])
    {
        $limit = $options['limit'] ?? 10;
        $period = $options['period'] ?? '30_days';
        $contentType = $options['type'] ?? null;
        
        $cacheKey = "popular_content_" . md5(serialize($options));
        
        return Cache::remember($cacheKey, 600, function () use ($limit, $period, $contentType) {
            $query = DB::table('v_popular_content')
                ->where('status', 'published')
                ->whereNull('deleted_at');
                
            if ($contentType) {
                $query->where('type', $contentType);
            }
            
            // Apply period-specific sorting
            switch ($period) {
                case '24_hours':
                    $query->orderByDesc('views_today');
                    break;
                case '7_days':
                    $query->orderByDesc('views_7_days');
                    break;
                case '30_days':
                default:
                    $query->orderByDesc('engagement_score')
                          ->orderByDesc('views_30_days');
                    break;
            }
            
            return $query->limit($limit)->get();
        });
    }
    
    /**
     * Get trending content based on growth metrics.
     * 
     * Identifies content with increasing popularity and engagement.
     * 
     * @param array $options Trending analysis options
     * @return \Illuminate\Support\Collection Trending content collection
     */
    public function getTrendingContent(array $options = []): \Illuminate\Support\Collection
    {
        $limit = $options['limit'] ?? 10;
        $cacheKey = "trending_content_" . md5(serialize($options));
        
        return Cache::remember($cacheKey, 300, function () use ($limit) {
            return DB::table('v_popular_content')
                ->where('status', 'published')
                ->whereNull('deleted_at')
                ->where('growth_rate_percentage', '>', 0)
                ->orderByDesc('growth_rate_percentage')
                ->orderByDesc('views_7_days')
                ->limit($limit)
                ->get();
        });
    }
    
    /**
     * Get real-time visit statistics.
     * 
     * Returns current active visitors and recent activity metrics.
     * 
     * @return array Real-time statistics
     */
    public function getRealTimeStats(): array
    {
        $cacheKey = 'realtime_visit_stats';
        
        return Cache::remember($cacheKey, 30, function () {
            $now = now();
            
            return [
                'active_visitors' => $this->getActiveVisitors(),
                'visits_last_hour' => $this->getVisitsInPeriod($now->subHour(), $now),
                'popular_content_now' => $this->getCurrentPopularContent(),
                'top_referrers' => $this->getTopReferrers(24),
                'device_breakdown' => $this->getCurrentDeviceBreakdown(),
                'geographic_distribution' => $this->getCurrentGeographicDistribution()
            ];
        });
    }
    
    /**
     * Update content statistics after a visit.
     * 
     * Increments view counts and updates last viewed timestamp.
     * 
     * @param Content $content Content being visited
     * @param \App\Models\User|null $user Visiting user
     * @return void
     */
    protected function updateContentStats(Content $content, $user): void
    {
        // Increment view count
        $content->increment('view_count');
        $content->touch('last_viewed_at');
        
        // Update unique visitor count if user is authenticated
        if ($user) {
            $hasVisited = ContentAccessLog::where('content_id', $content->id)
                ->where('user_id', $user->id)
                ->where('access_result', ContentAccessLog::RESULT_SUCCESS)
                ->exists();
                
            if (!$hasVisited) {
                $content->increment('unique_visitors');
            }
        }
    }
    
    /**
     * Track user activity for analytics.
     * 
     * Creates user activity record for behavior analysis.
     * 
     * @param Content $content Content being visited
     * @param \App\Models\User|null $user Visiting user
     * @param Request $request HTTP request instance
     * @return void
     */
    protected function trackUserActivity(Content $content, $user, Request $request): void
    {
        UserActivity::create([
            'user_id' => $user?->id,
            'activity_type' => 'content_view',
            'subject_type' => Content::class,
            'subject_id' => $content->id,
            'description' => "Viewed content: {$content->title}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'properties' => [
                'content_type' => $content->type,
                'content_category' => $content->category,
                'device_type' => $this->detectDeviceType($request->userAgent()),
                'referrer' => $request->header('referer'),
                'session_id' => session()->getId()
            ]
        ]);
    }
    
    /**
     * Update real-time analytics cache.
     * 
     * Updates cached analytics data for real-time dashboard display.
     * 
     * @param Content $content Content being visited
     * @param \App\Models\User|null $user Visiting user
     * @return void
     */
    protected function updateRealTimeAnalytics(Content $content, $user): void
    {
        // Update active content cache
        $activeContentKey = 'active_content_' . now()->format('Y-m-d-H-i');
        $activeContent = Cache::get($activeContentKey, []);
        $activeContent[$content->id] = ($activeContent[$content->id] ?? 0) + 1;
        Cache::put($activeContentKey, $activeContent, 3600);
        
        // Update visitor count cache
        if ($user) {
            $activeUsersKey = 'active_users_' . now()->format('Y-m-d-H');
            $activeUsers = Cache::get($activeUsersKey, []);
            $activeUsers[$user->id] = now()->timestamp;
            Cache::put($activeUsersKey, $activeUsers, 3600);
        }
    }
    
    /**
     * Get basic visit statistics for content.
     * 
     * @param Content $content Content to analyze
     * @param array $dateRange Date range for analysis
     * @return array Basic statistics
     */
    protected function getBasicStats(Content $content, array $dateRange): array
    {
        return [
            'total_views' => $content->view_count,
            'unique_visitors' => $content->unique_visitors ?? 0,
            'views_in_period' => $this->getViewsInPeriod($content, $dateRange),
            'avg_session_duration' => $this->getAverageSessionDuration($content, $dateRange),
            'bounce_rate' => $this->getBounceRate($content, $dateRange),
            'return_visitor_rate' => $this->getReturnVisitorRate($content, $dateRange)
        ];
    }
    
    /**
     * Get visitor analytics for content.
     * 
     * @param Content $content Content to analyze
     * @param array $dateRange Date range for analysis
     * @return array Visitor analytics
     */
    protected function getVisitorAnalytics(Content $content, array $dateRange): array
    {
        return [
            'new_visitors' => $this->getNewVisitors($content, $dateRange),
            'returning_visitors' => $this->getReturningVisitors($content, $dateRange),
            'visitor_frequency' => $this->getVisitorFrequency($content, $dateRange),
            'visitor_loyalty' => $this->getVisitorLoyalty($content, $dateRange)
        ];
    }
    
    /**
     * Get engagement metrics for content.
     * 
     * @param Content $content Content to analyze
     * @param array $dateRange Date range for analysis
     * @return array Engagement metrics
     */
    protected function getEngagementMetrics(Content $content, array $dateRange): array
    {
        return [
            'avg_time_on_page' => $this->getAverageTimeOnPage($content, $dateRange),
            'scroll_depth' => $this->getAverageScrollDepth($content, $dateRange),
            'reading_completion_rate' => $this->getReadingCompletionRate($content, $dateRange),
            'engagement_score' => $this->calculateEngagementScore($content, $dateRange)
        ];
    }
    
    /**
     * Estimate reading time for content.
     * 
     * @param Content $content Content to analyze
     * @return int Estimated reading time in minutes
     */
    protected function estimateReadingTime(Content $content): int
    {
        $wordCount = str_word_count(strip_tags($content->content ?? ''));
        $averageWPM = 200; // Average reading speed
        
        return max(1, ceil($wordCount / $averageWPM));
    }
    
    /**
     * Get country code from IP address.
     * 
     * @param string $ipAddress IP address to lookup
     * @return string|null Country code
     */
    protected function getCountryCode(string $ipAddress): ?string
    {
        // Placeholder for IP geolocation service
        // In production, integrate with MaxMind GeoIP or similar service
        return null;
    }
    
    /**
     * Get city from IP address.
     * 
     * @param string $ipAddress IP address to lookup
     * @return string|null City name
     */
    protected function getCity(string $ipAddress): ?string
    {
        // Placeholder for IP geolocation service
        // In production, integrate with MaxMind GeoIP or similar service
        return null;
    }
    
    /**
     * Detect device type from user agent.
     * 
     * @param string|null $userAgent User agent string
     * @return string Device type
     */
    protected function detectDeviceType(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'unknown';
        }
        
        $userAgent = strtolower($userAgent);
        
        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobile))/i', $userAgent)) {
            return 'tablet';
        }
        
        if (preg_match('/(mobile|iphone|ipod|blackberry|android|palm|windows\sce)/i', $userAgent)) {
            return 'mobile';
        }
        
        return 'desktop';
    }
    
    /**
     * Get date range for analytics queries.
     * 
     * @param array $options Options containing date range
     * @return array Start and end dates
     */
    protected function getDateRange(array $options): array
    {
        $endDate = $options['end_date'] ?? now();
        $startDate = $options['start_date'] ?? now()->subDays(30);
        
        return [
            'start' => Carbon::parse($startDate),
            'end' => Carbon::parse($endDate)
        ];
    }
    
    /**
     * Get active visitors count.
     * 
     * @return int Number of active visitors
     */
    protected function getActiveVisitors(): int
    {
        return ContentAccessLog::where('created_at', '>=', now()->subMinutes(30))
            ->distinct('session_id')
            ->count();
    }
    
    /**
     * Get visits in a specific time period.
     * 
     * @param Carbon $start Start time
     * @param Carbon $end End time
     * @return int Number of visits
     */
    protected function getVisitsInPeriod(Carbon $start, Carbon $end): int
    {
        return ContentAccessLog::whereBetween('created_at', [$start, $end])
            ->where('access_result', ContentAccessLog::RESULT_SUCCESS)
            ->count();
    }
    
    /**
     * Get currently popular content.
     * 
     * @return \Illuminate\Support\Collection Popular content
     */
    protected function getCurrentPopularContent()
    {
        return DB::table('v_popular_content')
            ->where('status', 'published')
            ->orderByDesc('views_today')
            ->limit(5)
            ->get(['id', 'title', 'views_today']);
    }
    
    /**
     * Get top referrers for a time period.
     * 
     * @param int $hours Hours to look back
     * @return array Top referrers
     */
    protected function getTopReferrers(int $hours): array
    {
        return ContentAccessLog::where('created_at', '>=', now()->subHours($hours))
            ->whereNotNull('referer')
            ->select('referer', DB::raw('COUNT(*) as count'))
            ->groupBy('referer')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'referer')
            ->toArray();
    }
    
    /**
     * Get current device breakdown.
     * 
     * @return array Device type distribution
     */
    protected function getCurrentDeviceBreakdown(): array
    {
        return ContentAccessLog::where('created_at', '>=', now()->subHour())
            ->select('device_type', DB::raw('COUNT(*) as count'))
            ->groupBy('device_type')
            ->pluck('count', 'device_type')
            ->toArray();
    }
    
    /**
     * Get current geographic distribution.
     * 
     * @return array Geographic distribution
     */
    protected function getCurrentGeographicDistribution(): array
    {
        return ContentAccessLog::where('created_at', '>=', now()->subHour())
            ->whereNotNull('country_code')
            ->select('country_code', DB::raw('COUNT(*) as count'))
            ->groupBy('country_code')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'country_code')
            ->toArray();
    }
    
    // Additional helper methods for analytics calculations
    // These would be implemented based on specific business requirements
    
    protected function getViewsInPeriod(Content $content, array $dateRange): int { return 0; }
    protected function getAverageSessionDuration(Content $content, array $dateRange): float { return 0.0; }
    protected function getBounceRate(Content $content, array $dateRange): float { return 0.0; }
    protected function getReturnVisitorRate(Content $content, array $dateRange): float { return 0.0; }
    protected function getNewVisitors(Content $content, array $dateRange): int { return 0; }
    protected function getReturningVisitors(Content $content, array $dateRange): int { return 0; }
    protected function getVisitorFrequency(Content $content, array $dateRange): array { return []; }
    protected function getVisitorLoyalty(Content $content, array $dateRange): array { return []; }
    protected function getAverageTimeOnPage(Content $content, array $dateRange): float { return 0.0; }
    protected function getAverageScrollDepth(Content $content, array $dateRange): float { return 0.0; }
    protected function getReadingCompletionRate(Content $content, array $dateRange): float { return 0.0; }
    protected function calculateEngagementScore(Content $content, array $dateRange): float { return 0.0; }
    protected function getDeviceBreakdown(Content $content, array $dateRange): array { return []; }
    protected function getGeographicData(Content $content, array $dateRange): array { return []; }
    protected function getTimeAnalytics(Content $content, array $dateRange): array { return []; }
    protected function getReferrerData(Content $content, array $dateRange): array { return []; }
    protected function getPerformanceMetrics(Content $content, array $dateRange): array { return []; }
}