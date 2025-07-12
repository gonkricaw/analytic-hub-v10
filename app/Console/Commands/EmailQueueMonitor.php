<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EmailQueue;
use App\Services\EmailQueueService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * EmailQueueMonitor Command
 * 
 * Console command for monitoring and managing the email queue system.
 * Provides real-time statistics, health checks, and maintenance operations.
 * 
 * Features:
 * - Queue statistics and health monitoring
 * - Failed email detection and reporting
 * - Performance metrics analysis
 * - Cleanup operations for old records
 * - Queue health alerts
 * 
 * @package App\Console\Commands
 * @author Analytics Hub Team
 * @version 1.0.0
 */
class EmailQueueMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:monitor 
                            {--stats : Show queue statistics}
                            {--health : Perform health check}
                            {--failed : Show failed emails}
                            {--cleanup : Clean up old records}
                            {--alerts : Check for alerts}
                            {--watch : Watch mode (continuous monitoring)}
                            {--interval=30 : Watch interval in seconds}
                            {--days=30 : Days to keep old records}
                            {--limit=100 : Limit for failed emails display}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor and manage the email queue system';

    /**
     * Email queue service instance
     *
     * @var EmailQueueService
     */
    protected $emailQueueService;

    /**
     * Create a new command instance.
     *
     * @param EmailQueueService $emailQueueService
     */
    public function __construct(EmailQueueService $emailQueueService)
    {
        parent::__construct();
        $this->emailQueueService = $emailQueueService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        try {
            $this->info('Email Queue Monitor Started');
            $this->info('Time: ' . now()->format('Y-m-d H:i:s'));
            $this->newLine();

            // Handle watch mode
            if ($this->option('watch')) {
                return $this->watchMode();
            }

            // Handle specific operations
            if ($this->option('stats')) {
                $this->showStatistics();
            }

            if ($this->option('health')) {
                $this->performHealthCheck();
            }

            if ($this->option('failed')) {
                $this->showFailedEmails();
            }

            if ($this->option('cleanup')) {
                $this->performCleanup();
            }

            if ($this->option('alerts')) {
                $this->checkAlerts();
            }

            // If no specific option, show overview
            if (!$this->option('stats') && !$this->option('health') && 
                !$this->option('failed') && !$this->option('cleanup') && 
                !$this->option('alerts')) {
                $this->showOverview();
            }

            return Command::SUCCESS;

        } catch (Exception $e) {
            $this->error('Error in email queue monitor: ' . $e->getMessage());
            Log::error('Email queue monitor error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Watch mode for continuous monitoring
     *
     * @return int
     */
    protected function watchMode(): int
    {
        $interval = (int) $this->option('interval');
        
        $this->info("Starting watch mode (interval: {$interval}s)");
        $this->info('Press Ctrl+C to stop');
        $this->newLine();

        while (true) {
            $this->line('\033[2J\033[H'); // Clear screen
            $this->info('Email Queue Monitor - Watch Mode');
            $this->info('Time: ' . now()->format('Y-m-d H:i:s'));
            $this->info('Refresh interval: ' . $interval . 's');
            $this->newLine();

            $this->showOverview();
            $this->checkAlerts();

            sleep($interval);
        }

        return Command::SUCCESS;
    }

    /**
     * Show overview of email queue
     *
     * @return void
     */
    protected function showOverview(): void
    {
        $this->showStatistics();
        $this->newLine();
        $this->performHealthCheck();
    }

    /**
     * Show queue statistics
     *
     * @return void
     */
    protected function showStatistics(): void
    {
        $this->info('📊 Queue Statistics');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $stats = $this->emailQueueService->getQueueStatistics();

        // Overall statistics
        $this->table(
            ['Metric', 'Count', 'Percentage'],
            [
                ['Total Emails', number_format($stats['total']), '100%'],
                ['Queued', number_format($stats['queued']), $this->calculatePercentage($stats['queued'], $stats['total'])],
                ['Processing', number_format($stats['processing']), $this->calculatePercentage($stats['processing'], $stats['total'])],
                ['Sent', number_format($stats['sent']), $this->calculatePercentage($stats['sent'], $stats['total'])],
                ['Failed', number_format($stats['failed']), $this->calculatePercentage($stats['failed'], $stats['total'])],
                ['Cancelled', number_format($stats['cancelled']), $this->calculatePercentage($stats['cancelled'], $stats['total'])],
                ['Expired', number_format($stats['expired']), $this->calculatePercentage($stats['expired'], $stats['total'])],
            ]
        );

        // Priority breakdown
        if (isset($stats['by_priority'])) {
            $this->newLine();
            $this->info('📋 By Priority');
            $priorityData = [];
            foreach ($stats['by_priority'] as $priority => $count) {
                $priorityData[] = [
                    ucfirst($priority),
                    number_format($count),
                    $this->calculatePercentage($count, $stats['total'])
                ];
            }
            $this->table(['Priority', 'Count', 'Percentage'], $priorityData);
        }

        // Type breakdown
        if (isset($stats['by_type'])) {
            $this->newLine();
            $this->info('📧 By Type');
            $typeData = [];
            foreach ($stats['by_type'] as $type => $count) {
                $typeData[] = [
                    ucfirst($type),
                    number_format($count),
                    $this->calculatePercentage($count, $stats['total'])
                ];
            }
            $this->table(['Type', 'Count', 'Percentage'], $typeData);
        }

        // Recent activity (last 24 hours)
        $recentStats = $this->getRecentActivity();
        if ($recentStats['total'] > 0) {
            $this->newLine();
            $this->info('🕐 Last 24 Hours');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Processed', number_format($recentStats['total'])],
                    ['Successfully Sent', number_format($recentStats['sent'])],
                    ['Failed', number_format($recentStats['failed'])],
                    ['Average Processing Time', $recentStats['avg_processing_time'] . 'ms'],
                ]
            );
        }
    }

    /**
     * Perform health check
     *
     * @return void
     */
    protected function performHealthCheck(): void
    {
        $this->info('🏥 Health Check');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $health = $this->performHealthChecks();
        
        foreach ($health as $check) {
            $status = $check['status'] === 'healthy' ? '✅' : ($check['status'] === 'warning' ? '⚠️' : '❌');
            $this->line("{$status} {$check['name']}: {$check['message']}");
        }

        $healthyCount = count(array_filter($health, fn($h) => $h['status'] === 'healthy'));
        $warningCount = count(array_filter($health, fn($h) => $h['status'] === 'warning'));
        $errorCount = count(array_filter($health, fn($h) => $h['status'] === 'error'));

        $this->newLine();
        $this->info("Health Summary: {$healthyCount} healthy, {$warningCount} warnings, {$errorCount} errors");
    }

    /**
     * Show failed emails
     *
     * @return void
     */
    protected function showFailedEmails(): void
    {
        $limit = (int) $this->option('limit');
        
        $this->info('❌ Failed Emails');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $failedEmails = EmailQueue::where('status', EmailQueue::STATUS_FAILED)
            ->orderBy('failed_at', 'desc')
            ->limit($limit)
            ->get();

        if ($failedEmails->isEmpty()) {
            $this->info('No failed emails found.');
            return;
        }

        $tableData = [];
        foreach ($failedEmails as $email) {
            $tableData[] = [
                substr($email->id, 0, 8) . '...',
                $email->to_email,
                substr($email->subject, 0, 30) . '...',
                $email->attempts,
                $email->failed_at ? $email->failed_at->format('Y-m-d H:i') : 'N/A',
                substr($email->error_message ?? 'Unknown error', 0, 40) . '...'
            ];
        }

        $this->table(
            ['ID', 'To Email', 'Subject', 'Attempts', 'Failed At', 'Error'],
            $tableData
        );

        $this->newLine();
        $this->info("Showing {$failedEmails->count()} of failed emails (limit: {$limit})");
    }

    /**
     * Perform cleanup operations
     *
     * @return void
     */
    protected function performCleanup(): void
    {
        $days = (int) $this->option('days');
        
        $this->info('🧹 Cleanup Operations');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        if ($this->confirm("This will delete email records older than {$days} days. Continue?")) {
            $result = $this->emailQueueService->cleanupOldEmails($days);
            
            $this->info("✅ Cleanup completed:");
            $this->line("   • Deleted {$result['deleted']} old email records");
            $this->line("   • Freed up approximately {$result['size_freed']} of storage");
            
            Log::info('Email queue cleanup completed', $result);
        } else {
            $this->info('Cleanup cancelled.');
        }
    }

    /**
     * Check for alerts
     *
     * @return void
     */
    protected function checkAlerts(): void
    {
        $alerts = $this->generateAlerts();
        
        if (empty($alerts)) {
            if (!$this->option('watch')) {
                $this->info('🟢 No alerts detected.');
            }
            return;
        }

        $this->newLine();
        $this->error('🚨 ALERTS DETECTED');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        foreach ($alerts as $alert) {
            $icon = $alert['level'] === 'critical' ? '🔴' : ($alert['level'] === 'warning' ? '🟡' : '🔵');
            $this->line("{$icon} {$alert['message']}");
        }

        // Log alerts
        foreach ($alerts as $alert) {
            Log::warning('Email queue alert', $alert);
        }
    }

    /**
     * Calculate percentage
     *
     * @param int $value
     * @param int $total
     * @return string
     */
    protected function calculatePercentage(int $value, int $total): string
    {
        if ($total === 0) {
            return '0%';
        }
        
        return number_format(($value / $total) * 100, 1) . '%';
    }

    /**
     * Get recent activity statistics
     *
     * @return array
     */
    protected function getRecentActivity(): array
    {
        $since = now()->subDay();
        
        $total = EmailQueue::where('created_at', '>=', $since)->count();
        $sent = EmailQueue::where('created_at', '>=', $since)
            ->where('status', EmailQueue::STATUS_SENT)
            ->count();
        $failed = EmailQueue::where('created_at', '>=', $since)
            ->where('status', EmailQueue::STATUS_FAILED)
            ->count();
        
        $avgProcessingTime = EmailQueue::where('created_at', '>=', $since)
            ->whereNotNull('processing_time_ms')
            ->avg('processing_time_ms');

        return [
            'total' => $total,
            'sent' => $sent,
            'failed' => $failed,
            'avg_processing_time' => $avgProcessingTime ? round($avgProcessingTime) : 0
        ];
    }

    /**
     * Perform health checks
     *
     * @return array
     */
    protected function performHealthChecks(): array
    {
        $checks = [];

        // Check for stuck processing emails
        $stuckCount = EmailQueue::where('status', EmailQueue::STATUS_PROCESSING)
            ->where('updated_at', '<', now()->subHour())
            ->count();
        
        $checks[] = [
            'name' => 'Stuck Processing Emails',
            'status' => $stuckCount === 0 ? 'healthy' : ($stuckCount < 10 ? 'warning' : 'error'),
            'message' => $stuckCount === 0 ? 'No stuck emails' : "{$stuckCount} emails stuck in processing"
        ];

        // Check failed email rate
        $recentTotal = EmailQueue::where('created_at', '>=', now()->subHour())->count();
        $recentFailed = EmailQueue::where('created_at', '>=', now()->subHour())
            ->where('status', EmailQueue::STATUS_FAILED)
            ->count();
        
        $failureRate = $recentTotal > 0 ? ($recentFailed / $recentTotal) * 100 : 0;
        
        $checks[] = [
            'name' => 'Failure Rate (Last Hour)',
            'status' => $failureRate < 5 ? 'healthy' : ($failureRate < 15 ? 'warning' : 'error'),
            'message' => number_format($failureRate, 1) . '% failure rate'
        ];

        // Check queue backlog
        $pendingCount = EmailQueue::where('status', EmailQueue::STATUS_PENDING)->count();
        
        $checks[] = [
            'name' => 'Queue Backlog',
            'status' => $pendingCount < 1000 ? 'healthy' : ($pendingCount < 5000 ? 'warning' : 'error'),
            'message' => number_format($pendingCount) . ' emails pending'
        ];

        // Check expired emails
        $expiredCount = EmailQueue::where('expires_at', '<', now())
            ->where('status', EmailQueue::STATUS_PENDING)
            ->count();
        
        $checks[] = [
            'name' => 'Expired Emails',
            'status' => $expiredCount === 0 ? 'healthy' : 'warning',
            'message' => $expiredCount === 0 ? 'No expired emails' : "{$expiredCount} expired emails need cleanup"
        ];

        return $checks;
    }

    /**
     * Generate alerts based on queue conditions
     *
     * @return array
     */
    protected function generateAlerts(): array
    {
        $alerts = [];

        // High failure rate alert
        $recentTotal = EmailQueue::where('created_at', '>=', now()->subHour())->count();
        $recentFailed = EmailQueue::where('created_at', '>=', now()->subHour())
            ->where('status', EmailQueue::STATUS_FAILED)
            ->count();
        
        if ($recentTotal > 10 && ($recentFailed / $recentTotal) > 0.2) {
            $alerts[] = [
                'level' => 'critical',
                'type' => 'high_failure_rate',
                'message' => 'High email failure rate detected (>20% in last hour)',
                'data' => ['total' => $recentTotal, 'failed' => $recentFailed]
            ];
        }

        // Large queue backlog alert
        $pendingCount = EmailQueue::where('status', EmailQueue::STATUS_PENDING)->count();
        if ($pendingCount > 10000) {
            $alerts[] = [
                'level' => 'warning',
                'type' => 'large_backlog',
                'message' => "Large email queue backlog: {$pendingCount} emails pending",
                'data' => ['pending_count' => $pendingCount]
            ];
        }

        // Stuck processing emails alert
        $stuckCount = EmailQueue::where('status', EmailQueue::STATUS_PROCESSING)
            ->where('updated_at', '<', now()->subHours(2))
            ->count();
        
        if ($stuckCount > 0) {
            $alerts[] = [
                'level' => 'warning',
                'type' => 'stuck_processing',
                'message' => "{$stuckCount} emails stuck in processing for >2 hours",
                'data' => ['stuck_count' => $stuckCount]
            ];
        }

        return $alerts;
    }
}