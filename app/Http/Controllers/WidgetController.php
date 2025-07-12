<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Content;
use App\Models\Notification;
use App\Models\UserActivity;
use Carbon\Carbon;

/**
 * Widget Controller
 * 
 * Handles API endpoints for dashboard widgets including:
 * - Digital clock data
 * - Login activity charts
 * - Active users statistics
 * - Online users count
 * - Popular content lists
 * - Latest announcements
 * - New users data
 * - Marquee text content
 * - Image banner data
 * 
 * All widget data is cached for performance optimization
 * Cache durations vary based on data update frequency
 */
class WidgetController extends Controller
{
    /**
     * Get digital clock data
     * 
     * Returns current server time and date
     * Cache: 1 second (real-time updates)
     */
    public function getClock(): JsonResponse
    {
        try {
            $now = Carbon::now();
            
            $data = [
                'time' => $now->format('H:i:s'),
                'date' => $now->format('l, F j, Y'),
                'timezone' => $now->timezoneName,
                'timestamp' => $now->timestamp
            ];
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get clock data',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get login activity chart data
     * 
     * Returns 15-day login trend data for Chart.js
     * Cache: 5 minutes
     */
    public function getLoginActivity(): JsonResponse
    {
        try {
            $cacheKey = 'widget_login_activity';
            
            $data = Cache::remember($cacheKey, 300, function () {
                // Get last 15 days of login data
                $startDate = Carbon::now()->subDays(14)->startOfDay();
                $endDate = Carbon::now()->endOfDay();
                
                $loginData = DB::table('idbi_user_activities')
                    ->select(
                        DB::raw('DATE(created_at) as date'),
                        DB::raw('COUNT(DISTINCT user_id) as unique_logins'),
                        DB::raw('COUNT(*) as total_logins')
                    )
                    ->where('action', 'login')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->groupBy(DB::raw('DATE(created_at)'))
                    ->orderBy('date')
                    ->get();
                
                // Fill missing dates with zero values
                $labels = [];
                $uniqueLogins = [];
                $totalLogins = [];
                
                for ($i = 14; $i >= 0; $i--) {
                    $date = Carbon::now()->subDays($i)->format('Y-m-d');
                    $labels[] = Carbon::parse($date)->format('M j');
                    
                    $dayData = $loginData->firstWhere('date', $date);
                    $uniqueLogins[] = $dayData ? (int)$dayData->unique_logins : 0;
                    $totalLogins[] = $dayData ? (int)$dayData->total_logins : 0;
                }
                
                return [
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'label' => 'Unique Logins',
                            'data' => $uniqueLogins,
                            'borderColor' => '#FF7A00',
                            'backgroundColor' => 'rgba(255, 122, 0, 0.1)',
                            'tension' => 0.4
                        ],
                        [
                            'label' => 'Total Logins',
                            'data' => $totalLogins,
                            'borderColor' => '#0E0E44',
                            'backgroundColor' => 'rgba(14, 14, 68, 0.1)',
                            'tension' => 0.4
                        ]
                    ]
                ];
            });
            
            return response()->json([
                'success' => true,
                'chart' => $data
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get login activity data',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get top active users data
     * 
     * Returns monthly login leaders
     * Cache: 1 hour
     */
    public function getActiveUsers(): JsonResponse
    {
        try {
            $cacheKey = 'widget_active_users';
            
            $data = Cache::remember($cacheKey, 3600, function () {
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
                
                $activeUsers = DB::table('v_top_active_users')
                    ->select('name', 'email', 'login_count', 'last_login')
                    ->limit(5)
                    ->get()
                    ->map(function ($user) {
                        return [
                            'name' => $user->name,
                            'email' => $user->email,
                            'login_count' => (int)$user->login_count,
                            'last_login' => Carbon::parse($user->last_login)->diffForHumans(),
                            'avatar' => $this->getUserAvatar($user->email)
                        ];
                    });
                
                return $activeUsers;
            });
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get active users data',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get online users count
     * 
     * Returns real-time active sessions
     * Cache: 30 seconds
     */
    public function getOnlineUsers(): JsonResponse
    {
        try {
            $cacheKey = 'widget_online_users';
            
            $data = Cache::remember($cacheKey, 30, function () {
                // Users active in last 5 minutes
                $threshold = Carbon::now()->subMinutes(5);
                
                $onlineCount = DB::table('v_online_users')
                    ->where('last_activity', '>=', $threshold)
                    ->count();
                
                // Get recent activity
                $recentActivity = DB::table('idbi_user_activities')
                    ->join('idbi_users', 'idbi_user_activities.user_id', '=', 'idbi_users.id')
                    ->select('idbi_users.name', 'idbi_user_activities.created_at')
                    ->where('idbi_user_activities.created_at', '>=', $threshold)
                    ->orderBy('idbi_user_activities.created_at', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function ($activity) {
                        return [
                            'name' => $activity->name,
                            'time' => Carbon::parse($activity->created_at)->diffForHumans()
                        ];
                    });
                
                return [
                    'count' => $onlineCount,
                    'recent_activity' => $recentActivity
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get online users data',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get popular content data
     * 
     * Returns top 5 visited pages
     * Cache: 30 minutes
     */
    public function getPopularContent(): JsonResponse
    {
        try {
            $cacheKey = 'widget_popular_content';
            
            $data = Cache::remember($cacheKey, 1800, function () {
                $popularContent = DB::table('v_popular_content')
                    ->select('title', 'slug', 'visit_count', 'last_visited')
                    ->limit(5)
                    ->get()
                    ->map(function ($content) {
                        return [
                            'title' => $content->title,
                            'slug' => $content->slug,
                            'visit_count' => (int)$content->visit_count,
                            'last_visited' => Carbon::parse($content->last_visited)->diffForHumans(),
                            'url' => route('content.show', $content->slug)
                        ];
                    });
                
                return $popularContent;
            });
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get popular content data',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get latest announcements
     * 
     * Returns recent 10 notifications
     * Cache: 2 minutes
     */
    public function getAnnouncements(): JsonResponse
    {
        try {
            $cacheKey = 'widget_announcements';
            
            $data = Cache::remember($cacheKey, 120, function () {
                $announcements = Notification::where('type', 'announcement')
                    ->where('status', 'sent')
                    ->where(function ($query) {
                        $query->whereNull('expires_at')
                              ->orWhere('expires_at', '>', Carbon::now());
                    })
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function ($notification) {
                        return [
                            'id' => $notification->id,
                            'title' => $notification->title,
                            'message' => strip_tags($notification->message),
                            'priority' => $notification->priority,
                            'created_at' => $notification->created_at->diffForHumans(),
                            'url' => $notification->action_url
                        ];
                    });
                
                return $announcements;
            });
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get announcements data',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get new users data
     * 
     * Returns latest 5 invited users
     * Cache: 10 minutes
     */
    public function getNewUsers(): JsonResponse
    {
        try {
            $cacheKey = 'widget_new_users';
            
            $data = Cache::remember($cacheKey, 600, function () {
                $newUsers = User::select('name', 'email', 'created_at', 'status')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function ($user) {
                        return [
                            'name' => $user->name,
                            'email' => $user->email,
                            'status' => $user->status,
                            'created_at' => $user->created_at->diffForHumans(),
                            'avatar' => $this->getUserAvatar($user->email)
                        ];
                    });
                
                return $newUsers;
            });
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get new users data',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get marquee text content
     * 
     * Returns scrolling announcement text
     * Cache: 1 hour
     */
    public function getMarquee(): JsonResponse
    {
        try {
            $cacheKey = 'widget_marquee';
            
            $data = Cache::remember($cacheKey, 3600, function () {
                // Get marquee text from system config
                $marqueeText = DB::table('idbi_system_configs')
                    ->where('key', 'marquee_text')
                    ->value('value');
                
                if (!$marqueeText) {
                    $marqueeText = 'Welcome to Analytics Hub - Your centralized analytics platform';
                }
                
                return [
                    'text' => $marqueeText,
                    'speed' => 50, // pixels per second
                    'direction' => 'left'
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get marquee data',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get image banner data
     * 
     * Returns slideshow images with captions
     * Cache: 30 minutes
     */
    public function getBanner(): JsonResponse
    {
        try {
            $cacheKey = 'widget_banner';
            
            $data = Cache::remember($cacheKey, 1800, function () {
                // Get banner images from system config
                $bannerData = DB::table('idbi_system_configs')
                    ->where('key', 'banner_images')
                    ->value('value');
                
                $images = [];
                if ($bannerData) {
                    $images = json_decode($bannerData, true) ?: [];
                }
                
                // Default images if none configured
                if (empty($images)) {
                    $images = [
                        [
                            'url' => '/images/banner/default-1.jpg',
                            'caption' => 'Analytics Hub Dashboard',
                            'alt' => 'Dashboard Overview'
                        ],
                        [
                            'url' => '/images/banner/default-2.jpg',
                            'caption' => 'Real-time Data Insights',
                            'alt' => 'Data Analytics'
                        ]
                    ];
                }
                
                return [
                    'images' => $images,
                    'autoplay' => true,
                    'interval' => 5000, // 5 seconds
                    'transition' => 'fade'
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get banner data',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Clear widget cache
     * 
     * Clears all widget-related cache entries
     */
    public function clearCache(): JsonResponse
    {
        try {
            $cacheKeys = [
                'widget_login_activity',
                'widget_active_users',
                'widget_online_users',
                'widget_popular_content',
                'widget_announcements',
                'widget_new_users',
                'widget_marquee',
                'widget_banner'
            ];
            
            foreach ($cacheKeys as $key) {
                Cache::forget($key);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Widget cache cleared successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to clear widget cache',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get user avatar URL
     * 
     * @param string $email
     * @return string
     */
    private function getUserAvatar(string $email): string
    {
        // Check if user has custom avatar
        $avatar = DB::table('idbi_user_avatars')
            ->join('idbi_users', 'idbi_user_avatars.user_id', '=', 'idbi_users.id')
            ->where('idbi_users.email', $email)
            ->value('idbi_user_avatars.file_path');
        
        if ($avatar) {
            return asset('storage/' . $avatar);
        }
        
        // Generate Gravatar URL
        $hash = md5(strtolower(trim($email)));
        return "https://www.gravatar.com/avatar/{$hash}?d=identicon&s=40";
    }
}