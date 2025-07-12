<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Services\ContentVisitTracker;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Popular Content Analytics Controller
 * 
 * Handles comprehensive analytics for popular content including:
 * - Popular content dashboard
 * - Trending content analysis
 * - Content performance metrics
 * - Engagement analytics
 * - Growth tracking
 * - Export functionality
 */
class PopularContentAnalyticsController extends Controller
{
    /**
     * Content visit tracker service instance.
     * 
     * @var ContentVisitTracker
     */
    protected ContentVisitTracker $visitTracker;

    /**
     * Create a new controller instance.
     * 
     * @param ContentVisitTracker $visitTracker Visit tracking service
     */
    public function __construct(ContentVisitTracker $visitTracker)
    {
        $this->visitTracker = $visitTracker;
        $this->middleware('auth');
        $this->middleware('role:admin|content_manager');
    }

    /**
     * Display the popular content analytics dashboard.
     * 
     * Shows comprehensive analytics including popular content,
     * trending content, performance metrics, and growth indicators.
     * 
     * @param Request $request HTTP request instance
     * @return View Popular content analytics dashboard view
     */
    public function index(Request $request): View
    {
        try {
            // Get dashboard summary data
            $dashboardData = $this->getDashboardSummary();
            
            // Get popular content with different time periods
            $popularToday = $this->visitTracker->getPopularContent([
                'period' => '24_hours',
                'limit' => 10
            ]);
            
            $popularWeek = $this->visitTracker->getPopularContent([
                'period' => '7_days',
                'limit' => 10
            ]);
            
            $popularMonth = $this->visitTracker->getPopularContent([
                'period' => '30_days',
                'limit' => 10
            ]);
            
            // Get trending content
            $trendingContent = $this->visitTracker->getTrendingContent([
                'limit' => 10
            ]);
            
            // Get content performance metrics
            $performanceMetrics = $this->getContentPerformanceMetrics();
            
            // Get engagement analytics
            $engagementAnalytics = $this->getEngagementAnalytics();
            
            return view('admin.analytics.popular-content.index', compact(
                'dashboardData',
                'popularToday',
                'popularWeek',
                'popularMonth',
                'trendingContent',
                'performanceMetrics',
                'engagementAnalytics'
            ));
            
        } catch (\Exception $e) {
            Log::error('Failed to load popular content analytics dashboard', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return view('admin.analytics.popular-content.index')
                ->with('error', 'Failed to load analytics data.');
        }
    }

    /**
     * Get popular content data via AJAX.
     * 
     * Returns popular content based on specified criteria
     * for dynamic dashboard updates.
     * 
     * @param Request $request HTTP request instance
     * @return JsonResponse JSON response with popular content data
     */
    public function getPopularContent(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'period' => 'string|in:24_hours,7_days,30_days',
                'limit' => 'integer|min:1|max:100',
                'type' => 'string|nullable',
                'category' => 'string|nullable'
            ]);
            
            $options = [
                'period' => $validated['period'] ?? '30_days',
                'limit' => $validated['limit'] ?? 20,
                'type' => $validated['type'] ?? null,
                'category' => $validated['category'] ?? null
            ];
            
            $popularContent = $this->visitTracker->getPopularContent($options);
            
            return response()->json([
                'success' => true,
                'popular_content' => $popularContent,
                'options' => $options,
                'timestamp' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get popular content data', [
                'request' => $request->all(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve popular content data.'
            ], 500);
        }
    }

    /**
     * Get trending content analysis.
     * 
     * Returns content with increasing popularity and growth metrics.
     * 
     * @param Request $request HTTP request instance
     * @return JsonResponse JSON response with trending content data
     */
    public function getTrendingContent(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'limit' => 'integer|min:1|max:100',
                'min_growth_rate' => 'numeric|min:0',
                'type' => 'string|nullable'
            ]);
            
            $options = [
                'limit' => $validated['limit'] ?? 20,
                'min_growth_rate' => $validated['min_growth_rate'] ?? 10,
                'type' => $validated['type'] ?? null
            ];
            
            $trendingContent = $this->visitTracker->getTrendingContent($options);
            
            return response()->json([
                'success' => true,
                'trending_content' => $trendingContent,
                'options' => $options,
                'timestamp' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get trending content data', [
                'request' => $request->all(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve trending content data.'
            ], 500);
        }
    }

    /**
     * Get content performance comparison.
     * 
     * Compares content performance across different metrics
     * and time periods for analysis.
     * 
     * @param Request $request HTTP request instance
     * @return JsonResponse JSON response with performance comparison data
     */
    public function getPerformanceComparison(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'content_ids' => 'array|required',
                'content_ids.*' => 'integer|exists:contents,id',
                'metrics' => 'array',
                'metrics.*' => 'string|in:views,engagement,growth_rate,unique_viewers'
            ]);
            
            $contentIds = $validated['content_ids'];
            $metrics = $validated['metrics'] ?? ['views', 'engagement', 'growth_rate'];
            
            $comparisonData = $this->getContentComparisonData($contentIds, $metrics);
            
            return response()->json([
                'success' => true,
                'comparison_data' => $comparisonData,
                'content_ids' => $contentIds,
                'metrics' => $metrics,
                'timestamp' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get content performance comparison', [
                'request' => $request->all(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve performance comparison data.'
            ], 500);
        }
    }

    /**
     * Get engagement analytics data.
     * 
     * Returns detailed engagement metrics including time spent,
     * interaction rates, and user behavior patterns.
     * 
     * @param Request $request HTTP request instance
     * @return JsonResponse JSON response with engagement analytics
     */
    public function getEngagementAnalytics(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'period' => 'string|in:7_days,30_days,90_days',
                'content_type' => 'string|nullable',
                'category' => 'string|nullable'
            ]);
            
            $period = $validated['period'] ?? '30_days';
            $contentType = $validated['content_type'] ?? null;
            $category = $validated['category'] ?? null;
            
            $engagementData = $this->calculateEngagementMetrics($period, $contentType, $category);
            
            return response()->json([
                'success' => true,
                'engagement_analytics' => $engagementData,
                'period' => $period,
                'filters' => [
                    'content_type' => $contentType,
                    'category' => $category
                ],
                'timestamp' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get engagement analytics', [
                'request' => $request->all(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve engagement analytics.'
            ], 500);
        }
    }

    /**
     * Export popular content analytics data.
     * 
     * Generates CSV export of popular content analytics
     * with comprehensive metrics and insights.
     * 
     * @param Request $request HTTP request instance
     * @return \Symfony\Component\HttpFoundation\StreamedResponse CSV download response
     */
    public function exportAnalytics(Request $request)
    {
        try {
            $validated = $request->validate([
                'period' => 'string|in:7_days,30_days,90_days',
                'format' => 'string|in:csv,xlsx',
                'include_trending' => 'boolean',
                'include_engagement' => 'boolean'
            ]);
            
            $period = $validated['period'] ?? '30_days';
            $format = $validated['format'] ?? 'csv';
            $includeTrending = $validated['include_trending'] ?? true;
            $includeEngagement = $validated['include_engagement'] ?? true;
            
            $exportData = $this->prepareExportData($period, $includeTrending, $includeEngagement);
            
            $filename = 'popular-content-analytics-' . $period . '-' . now()->format('Y-m-d-H-i-s') . '.' . $format;
            
            if ($format === 'csv') {
                return $this->exportToCsv($exportData, $filename);
            } else {
                return $this->exportToExcel($exportData, $filename);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to export popular content analytics', [
                'request' => $request->all(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to export analytics data.'
            ], 500);
        }
    }

    /**
     * Get dashboard summary data.
     * 
     * Returns key metrics and summary statistics for the dashboard.
     * 
     * @return array Dashboard summary data
     */
    protected function getDashboardSummary(): array
    {
        $cacheKey = 'popular_content_dashboard_summary';
        
        return Cache::remember($cacheKey, 300, function () {
            $summary = DB::table('v_popular_content')
                ->selectRaw('
                    COUNT(*) as total_content,
                    SUM(views_last_30_days) as total_views_30_days,
                    SUM(views_last_7_days) as total_views_7_days,
                    SUM(views_today) as total_views_today,
                    AVG(engagement_score) as avg_engagement_score,
                    COUNT(CASE WHEN is_trending = 1 THEN 1 END) as trending_count,
                    MAX(views_last_30_days) as max_views_30_days,
                    AVG(growth_rate_percentage) as avg_growth_rate
                ')
                ->where('status', 'published')
                ->whereNull('deleted_at')
                ->first();
                
            return [
                'total_content' => $summary->total_content ?? 0,
                'total_views_30_days' => $summary->total_views_30_days ?? 0,
                'total_views_7_days' => $summary->total_views_7_days ?? 0,
                'total_views_today' => $summary->total_views_today ?? 0,
                'avg_engagement_score' => round($summary->avg_engagement_score ?? 0, 2),
                'trending_count' => $summary->trending_count ?? 0,
                'max_views_30_days' => $summary->max_views_30_days ?? 0,
                'avg_growth_rate' => round($summary->avg_growth_rate ?? 0, 2)
            ];
        });
    }

    /**
     * Get content performance metrics.
     * 
     * Returns performance metrics for content analysis.
     * 
     * @return array Content performance metrics
     */
    protected function getContentPerformanceMetrics(): array
    {
        $cacheKey = 'content_performance_metrics';
        
        return Cache::remember($cacheKey, 600, function () {
            // Get top performing content by category
            $topByCategory = DB::table('v_popular_content')
                ->select('category', DB::raw('COUNT(*) as content_count'), DB::raw('SUM(views_last_30_days) as total_views'))
                ->where('status', 'published')
                ->whereNull('deleted_at')
                ->whereNotNull('category')
                ->groupBy('category')
                ->orderByDesc('total_views')
                ->limit(10)
                ->get();
                
            // Get top performing content by type
            $topByType = DB::table('v_popular_content')
                ->select('type', DB::raw('COUNT(*) as content_count'), DB::raw('SUM(views_last_30_days) as total_views'))
                ->where('status', 'published')
                ->whereNull('deleted_at')
                ->whereNotNull('type')
                ->groupBy('type')
                ->orderByDesc('total_views')
                ->get();
                
            // Get performance trends over time
            $performanceTrends = DB::table('content_access_logs')
                ->selectRaw('
                    DATE(created_at) as date,
                    COUNT(*) as total_accesses,
                    COUNT(DISTINCT content_id) as unique_content,
                    COUNT(DISTINCT user_id) as unique_users
                ')
                ->where('created_at', '>=', now()->subDays(30))
                ->where('result', 'success')
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date')
                ->get();
                
            return [
                'top_by_category' => $topByCategory,
                'top_by_type' => $topByType,
                'performance_trends' => $performanceTrends
            ];
        });
    }

    /**
     * Calculate engagement analytics.
     * 
     * Returns detailed engagement metrics and user behavior patterns.
     * 
     * @return array Engagement analytics data
     */
    protected function getEngagementAnalytics(): array
    {
        $cacheKey = 'engagement_analytics_summary';
        
        return Cache::remember($cacheKey, 600, function () {
            // Get engagement score distribution
            $engagementDistribution = DB::table('v_popular_content')
                ->selectRaw('
                    CASE 
                        WHEN engagement_score >= 100 THEN "High (100+)"
                        WHEN engagement_score >= 50 THEN "Medium (50-99)"
                        WHEN engagement_score >= 10 THEN "Low (10-49)"
                        ELSE "Very Low (0-9)"
                    END as engagement_level,
                    COUNT(*) as content_count
                ')
                ->where('status', 'published')
                ->whereNull('deleted_at')
                ->groupBy(DB::raw('
                    CASE 
                        WHEN engagement_score >= 100 THEN "High (100+)"
                        WHEN engagement_score >= 50 THEN "Medium (50-99)"
                        WHEN engagement_score >= 10 THEN "Low (10-49)"
                        ELSE "Very Low (0-9)"
                    END
                '))
                ->get();
                
            // Get user engagement patterns
            $userEngagement = DB::table('content_access_logs')
                ->selectRaw('
                    COUNT(DISTINCT user_id) as total_users,
                    COUNT(*) as total_accesses,
                    AVG(session_duration) as avg_session_duration,
                    COUNT(CASE WHEN session_duration > 300 THEN 1 END) as long_sessions
                ')
                ->where('created_at', '>=', now()->subDays(30))
                ->where('result', 'success')
                ->whereNotNull('user_id')
                ->first();
                
            return [
                'engagement_distribution' => $engagementDistribution,
                'user_engagement' => $userEngagement,
                'avg_engagement_score' => DB::table('v_popular_content')
                    ->where('status', 'published')
                    ->whereNull('deleted_at')
                    ->avg('engagement_score') ?? 0
            ];
        });
    }

    /**
     * Get content comparison data.
     * 
     * Compares multiple content items across specified metrics.
     * 
     * @param array $contentIds Content IDs to compare
     * @param array $metrics Metrics to include in comparison
     * @return array Comparison data
     */
    protected function getContentComparisonData(array $contentIds, array $metrics): array
    {
        $comparisonData = [];
        
        foreach ($contentIds as $contentId) {
            $contentData = DB::table('v_popular_content')
                ->where('id', $contentId)
                ->first();
                
            if ($contentData) {
                $comparison = [
                    'id' => $contentData->id,
                    'title' => $contentData->title,
                    'type' => $contentData->type,
                    'category' => $contentData->category
                ];
                
                foreach ($metrics as $metric) {
                    switch ($metric) {
                        case 'views':
                            $comparison['views_30_days'] = $contentData->views_last_30_days ?? 0;
                            $comparison['views_7_days'] = $contentData->views_last_7_days ?? 0;
                            $comparison['views_today'] = $contentData->views_today ?? 0;
                            break;
                        case 'engagement':
                            $comparison['engagement_score'] = $contentData->engagement_score ?? 0;
                            break;
                        case 'growth_rate':
                            $comparison['growth_rate_percentage'] = $contentData->growth_rate_percentage ?? 0;
                            $comparison['is_trending'] = $contentData->is_trending ?? 0;
                            break;
                        case 'unique_viewers':
                            $comparison['unique_viewers_30_days'] = $contentData->unique_viewers_30_days ?? 0;
                            $comparison['unique_viewers_7_days'] = $contentData->unique_viewers_7_days ?? 0;
                            break;
                    }
                }
                
                $comparisonData[] = $comparison;
            }
        }
        
        return $comparisonData;
    }

    /**
     * Calculate engagement metrics for specified period.
     * 
     * @param string $period Time period for analysis
     * @param string|null $contentType Content type filter
     * @param string|null $category Category filter
     * @return array Engagement metrics
     */
    protected function calculateEngagementMetrics(string $period, ?string $contentType, ?string $category): array
    {
        $days = match($period) {
            '7_days' => 7,
            '30_days' => 30,
            '90_days' => 90,
            default => 30
        };
        
        $query = DB::table('v_popular_content')
            ->where('status', 'published')
            ->whereNull('deleted_at');
            
        if ($contentType) {
            $query->where('type', $contentType);
        }
        
        if ($category) {
            $query->where('category', $category);
        }
        
        $metrics = $query->selectRaw('
            AVG(engagement_score) as avg_engagement_score,
            MAX(engagement_score) as max_engagement_score,
            MIN(engagement_score) as min_engagement_score,
            COUNT(*) as total_content,
            SUM(CASE WHEN is_trending = 1 THEN 1 ELSE 0 END) as trending_content,
            AVG(growth_rate_percentage) as avg_growth_rate
        ')->first();
        
        return [
            'avg_engagement_score' => round($metrics->avg_engagement_score ?? 0, 2),
            'max_engagement_score' => round($metrics->max_engagement_score ?? 0, 2),
            'min_engagement_score' => round($metrics->min_engagement_score ?? 0, 2),
            'total_content' => $metrics->total_content ?? 0,
            'trending_content' => $metrics->trending_content ?? 0,
            'avg_growth_rate' => round($metrics->avg_growth_rate ?? 0, 2),
            'period' => $period,
            'filters' => [
                'content_type' => $contentType,
                'category' => $category
            ]
        ];
    }

    /**
     * Prepare data for export.
     * 
     * @param string $period Export period
     * @param bool $includeTrending Include trending data
     * @param bool $includeEngagement Include engagement data
     * @return array Export data
     */
    protected function prepareExportData(string $period, bool $includeTrending, bool $includeEngagement): array
    {
        $popularContent = $this->visitTracker->getPopularContent([
            'period' => $period,
            'limit' => 1000
        ]);
        
        $exportData = [];
        
        foreach ($popularContent as $content) {
            $row = [
                'ID' => $content->id,
                'Title' => $content->title,
                'Type' => $content->type,
                'Category' => $content->category,
                'Status' => $content->status,
                'Views (30 days)' => $content->views_last_30_days ?? 0,
                'Views (7 days)' => $content->views_last_7_days ?? 0,
                'Views (Today)' => $content->views_today ?? 0,
                'Unique Viewers (30 days)' => $content->unique_viewers_30_days ?? 0,
                'Engagement Score' => $content->engagement_score ?? 0,
                'Content Age (days)' => $content->content_age_days ?? 0,
                'Views per Day' => $content->views_per_day_since_publication ?? 0
            ];
            
            if ($includeTrending) {
                $row['Is Trending'] = $content->is_trending ? 'Yes' : 'No';
                $row['Growth Rate (%)'] = $content->growth_rate_percentage ?? 0;
            }
            
            if ($includeEngagement) {
                $row['Last View Date'] = $content->last_view_date ?? 'Never';
                $row['Days Since Last View'] = $content->days_since_last_view ?? 'N/A';
            }
            
            $exportData[] = $row;
        }
        
        return $exportData;
    }

    /**
     * Export data to CSV format.
     * 
     * @param array $data Export data
     * @param string $filename Export filename
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    protected function exportToCsv(array $data, string $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        return response()->stream(function () use ($data) {
            $handle = fopen('php://output', 'w');
            
            if (!empty($data)) {
                // Write headers
                fputcsv($handle, array_keys($data[0]));
                
                // Write data rows
                foreach ($data as $row) {
                    fputcsv($handle, $row);
                }
            }
            
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export data to Excel format.
     * 
     * @param array $data Export data
     * @param string $filename Export filename
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    protected function exportToExcel(array $data, string $filename)
    {
        // For now, fallback to CSV format
        // In a real implementation, you would use a library like PhpSpreadsheet
        return $this->exportToCsv($data, str_replace('.xlsx', '.csv', $filename));
    }
}