<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificationService;
use App\Models\Notification;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * NotificationStats Command
 * 
 * Displays comprehensive statistics about the notification system.
 * Useful for monitoring and debugging notification performance.
 */
class NotificationStats extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'notifications:stats 
                           {--format=table : Output format (table, json)}
                           {--period=30 : Period in days for statistics}
                           {--detailed : Show detailed breakdown}';

    /**
     * The console command description.
     */
    protected $description = 'Display notification system statistics and health metrics';

    /**
     * The notification service instance.
     */
    protected NotificationService $notificationService;

    /**
     * Create a new command instance.
     */
    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $format = $this->option('format');
        $period = (int) $this->option('period');
        $detailed = $this->option('detailed');
        
        try {
            $this->info("Notification System Statistics (Last {$period} days)");
            $this->line(str_repeat('=', 60));
            
            // Get basic statistics
            $stats = $this->notificationService->getStatistics();
            
            // Get period-specific statistics
            $periodStats = $this->getPeriodStatistics($period);
            
            // Combine statistics
            $allStats = array_merge($stats, $periodStats);
            
            if ($format === 'json') {
                $this->line(json_encode($allStats, JSON_PRETTY_PRINT));
            } else {
                $this->displayTableStats($allStats, $detailed);
            }
            
            // Health check
            $this->displayHealthCheck($allStats);
            
            return self::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Failed to generate notification statistics: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
    
    /**
     * Get statistics for a specific period.
     */
    protected function getPeriodStatistics(int $days): array
    {
        $startDate = Carbon::now()->subDays($days);
        
        return [
            'period_days' => $days,
            'period_notifications_created' => Notification::where('created_at', '>=', $startDate)->count(),
            'period_notifications_delivered' => UserNotification::where('delivered_at', '>=', $startDate)
                ->where('delivery_status', UserNotification::DELIVERY_STATUS_DELIVERED)
                ->count(),
            'period_notifications_failed' => UserNotification::where('failed_at', '>=', $startDate)
                ->where('delivery_status', UserNotification::DELIVERY_STATUS_FAILED)
                ->count(),
            'period_notifications_read' => UserNotification::where('read_at', '>=', $startDate)
                ->where('is_read', true)
                ->count(),
            'period_avg_read_time' => $this->getAverageReadTime($startDate),
        ];
    }
    
    /**
     * Calculate average time to read notifications.
     */
    protected function getAverageReadTime(Carbon $startDate): ?float
    {
        $readNotifications = UserNotification::select(
                DB::raw('TIMESTAMPDIFF(SECOND, created_at, read_at) as read_time_seconds')
            )
            ->where('read_at', '>=', $startDate)
            ->where('is_read', true)
            ->whereNotNull('read_at')
            ->get();
            
        if ($readNotifications->isEmpty()) {
            return null;
        }
        
        return $readNotifications->avg('read_time_seconds');
    }
    
    /**
     * Display statistics in table format.
     */
    protected function displayTableStats(array $stats, bool $detailed): void
    {
        // Basic statistics
        $this->info('\nBasic Statistics:');
        $basicStats = [
            ['Metric', 'Count'],
            ['Total Notifications', number_format($stats['total_notifications'])],
            ['Active Notifications', number_format($stats['active_notifications'])],
            ['Scheduled Notifications', number_format($stats['scheduled_notifications'])],
            ['Expired Notifications', number_format($stats['expired_notifications'])],
        ];
        $this->table($basicStats[0], array_slice($basicStats, 1));
        
        // User notification statistics
        $this->info('\nUser Notification Statistics:');
        $userStats = [
            ['Metric', 'Count'],
            ['Total User Notifications', number_format($stats['total_user_notifications'])],
            ['Unread Notifications', number_format($stats['unread_notifications'])],
            ['Read Notifications', number_format($stats['read_notifications'])],
            ['Dismissed Notifications', number_format($stats['dismissed_notifications'])],
            ['Failed Deliveries', number_format($stats['failed_deliveries'])],
        ];
        $this->table($userStats[0], array_slice($userStats, 1));
        
        // Period statistics
        if (isset($stats['period_days'])) {
            $this->info("\nLast {$stats['period_days']} Days Statistics:");
            $periodStats = [
                ['Metric', 'Count'],
                ['Notifications Created', number_format($stats['period_notifications_created'])],
                ['Notifications Delivered', number_format($stats['period_notifications_delivered'])],
                ['Notifications Failed', number_format($stats['period_notifications_failed'])],
                ['Notifications Read', number_format($stats['period_notifications_read'])],
            ];
            
            if ($stats['period_avg_read_time'] !== null) {
                $avgReadTime = round($stats['period_avg_read_time'] / 60, 2); // Convert to minutes
                $periodStats[] = ['Avg. Read Time (minutes)', $avgReadTime];
            }
            
            $this->table($periodStats[0], array_slice($periodStats, 1));
        }
        
        // Detailed breakdown
        if ($detailed) {
            $this->displayDetailedBreakdown();
        }
    }
    
    /**
     * Display detailed breakdown of notifications.
     */
    protected function displayDetailedBreakdown(): void
    {
        // Notifications by type
        $this->info('\nNotifications by Type:');
        $typeStats = Notification::select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function ($item) {
                return [ucfirst($item->type), number_format($item->count)];
            })
            ->toArray();
            
        if (!empty($typeStats)) {
            $this->table(['Type', 'Count'], $typeStats);
        }
        
        // Notifications by priority
        $this->info('\nNotifications by Priority:');
        $priorityStats = Notification::select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function ($item) {
                return [ucfirst($item->priority), number_format($item->count)];
            })
            ->toArray();
            
        if (!empty($priorityStats)) {
            $this->table(['Priority', 'Count'], $priorityStats);
        }
        
        // Delivery status breakdown
        $this->info('\nDelivery Status Breakdown:');
        $deliveryStats = UserNotification::select('delivery_status', DB::raw('count(*) as count'))
            ->groupBy('delivery_status')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function ($item) {
                return [ucfirst($item->delivery_status), number_format($item->count)];
            })
            ->toArray();
            
        if (!empty($deliveryStats)) {
            $this->table(['Status', 'Count'], $deliveryStats);
        }
    }
    
    /**
     * Display health check information.
     */
    protected function displayHealthCheck(array $stats): void
    {
        $this->info('\nSystem Health Check:');
        $this->line(str_repeat('-', 40));
        
        // Calculate health metrics
        $totalUserNotifications = $stats['total_user_notifications'];
        $failedDeliveries = $stats['failed_deliveries'];
        $unreadNotifications = $stats['unread_notifications'];
        
        // Delivery success rate
        if ($totalUserNotifications > 0) {
            $successRate = (($totalUserNotifications - $failedDeliveries) / $totalUserNotifications) * 100;
            $this->line(sprintf('Delivery Success Rate: %.2f%%', $successRate));
            
            if ($successRate >= 95) {
                $this->info('✓ Delivery rate is healthy');
            } elseif ($successRate >= 90) {
                $this->comment('⚠ Delivery rate needs attention');
            } else {
                $this->error('✗ Delivery rate is poor');
            }
        }
        
        // Read rate
        $readNotifications = $stats['read_notifications'];
        if ($totalUserNotifications > 0) {
            $readRate = ($readNotifications / $totalUserNotifications) * 100;
            $this->line(sprintf('Read Rate: %.2f%%', $readRate));
            
            if ($readRate >= 70) {
                $this->info('✓ Read rate is good');
            } elseif ($readRate >= 50) {
                $this->comment('⚠ Read rate could be improved');
            } else {
                $this->error('✗ Read rate is low');
            }
        }
        
        // Unread notifications warning
        if ($unreadNotifications > 1000) {
            $this->error('✗ High number of unread notifications detected');
        } elseif ($unreadNotifications > 500) {
            $this->comment('⚠ Moderate number of unread notifications');
        } else {
            $this->info('✓ Unread notifications count is manageable');
        }
        
        // Failed deliveries warning
        if ($failedDeliveries > 100) {
            $this->error('✗ High number of failed deliveries detected');
        } elseif ($failedDeliveries > 50) {
            $this->comment('⚠ Some failed deliveries detected');
        } else {
            $this->info('✓ Failed deliveries count is low');
        }
        
        // Scheduled notifications
        $scheduledNotifications = $stats['scheduled_notifications'];
        if ($scheduledNotifications > 0) {
            $this->info("ℹ {$scheduledNotifications} notifications are scheduled for future delivery");
        }
        
        // Expired notifications
        $expiredNotifications = $stats['expired_notifications'];
        if ($expiredNotifications > 100) {
            $this->comment("⚠ {$expiredNotifications} expired notifications should be cleaned up");
        }
    }
}