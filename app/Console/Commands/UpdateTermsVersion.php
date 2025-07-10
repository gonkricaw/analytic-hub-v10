<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TermsNotificationService;
use App\Models\SystemConfig;
use App\Models\User;

/**
 * UpdateTermsVersion Command
 * 
 * Provides administrative interface for updating Terms & Conditions version
 * and managing user notifications for T&C updates.
 */
class UpdateTermsVersion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'terms:update-version 
                            {version? : New version number (e.g., 1.1, 2.0)} 
                            {--reason= : Reason for the update} 
                            {--no-notify : Skip sending notifications to users} 
                            {--stats : Show current T&C statistics}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Terms & Conditions version and notify users';

    /**
     * Terms notification service
     * 
     * @var TermsNotificationService
     */
    protected $termsService;

    /**
     * Constructor
     * 
     * @param TermsNotificationService $termsService
     */
    public function __construct(TermsNotificationService $termsService)
    {
        parent::__construct();
        $this->termsService = $termsService;
    }

    /**
     * Execute the console command.
     * 
     * @return int
     */
    public function handle(): int
    {
        // Show current statistics if requested
        if ($this->option('stats')) {
            $this->showStatistics();
            return Command::SUCCESS;
        }

        $newVersion = $this->argument('version');
        $reason = $this->option('reason');
        $skipNotifications = $this->option('no-notify');

        // If no version provided and not showing stats, show error
        if (!$newVersion) {
            $this->error('Version argument is required unless using --stats option.');
            return Command::FAILURE;
        }

        // Validate version format
        if (!$this->validateVersion($newVersion)) {
            $this->error('Invalid version format. Please use semantic versioning (e.g., 1.0, 1.1, 2.0)');
            return Command::FAILURE;
        }

        $currentVersion = $this->termsService->getCurrentVersion();
        
        // Confirm the update
        $this->info("Current Terms & Conditions Version: {$currentVersion}");
        $this->info("New Version: {$newVersion}");
        
        if ($reason) {
            $this->info("Reason: {$reason}");
        }
        
        if ($skipNotifications) {
            $this->warn('Notifications will be skipped.');
        } else {
            $usersCount = User::where('status', 'active')->where('email_notifications', true)->count();
            $this->info("Notifications will be sent to {$usersCount} active users.");
        }

        if (!$this->confirm('Do you want to proceed with the update?')) {
            $this->info('Update cancelled.');
            return Command::SUCCESS;
        }

        // Temporarily disable notifications if requested
        $originalNotificationSetting = null;
        if ($skipNotifications) {
            $originalNotificationSetting = SystemConfig::get('terms.notification_enabled', true);
            SystemConfig::set('terms.notification_enabled', false);
        }

        // Perform the update
        $this->info('Updating Terms & Conditions version...');
        
        $success = $this->termsService->updateVersion($newVersion, $reason);
        
        // Restore notification setting if it was changed
        if ($skipNotifications && $originalNotificationSetting !== null) {
            SystemConfig::set('terms.notification_enabled', $originalNotificationSetting);
        }

        if ($success) {
            $this->info("✅ Terms & Conditions version updated successfully to {$newVersion}");
            
            if (!$skipNotifications) {
                $this->info('📧 User notifications have been sent.');
            }
            
            // Show updated statistics
            $this->newLine();
            $this->showStatistics();
            
            return Command::SUCCESS;
        } else {
            $this->error('❌ Failed to update Terms & Conditions version. Check logs for details.');
            return Command::FAILURE;
        }
    }

    /**
     * Validate version format
     * 
     * @param string $version
     * @return bool
     */
    private function validateVersion(string $version): bool
    {
        // Simple semantic versioning validation
        return preg_match('/^\d+\.\d+(\.\d+)?$/', $version);
    }

    /**
     * Show current T&C statistics
     * 
     * @return void
     */
    private function showStatistics(): void
    {
        $stats = $this->termsService->getUpdateStatistics();
        
        $this->info('📊 Terms & Conditions Statistics');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Current Version', $stats['current_version']],
                ['Last Updated', $stats['last_updated'] ? \Carbon\Carbon::parse($stats['last_updated'])->format('Y-m-d H:i:s') : 'Never'],
                ['Total Active Users', $stats['total_users']],
                ['Users Accepted Current Version', $stats['users_accepted']],
                ['Users Needing Acceptance', $stats['users_needing_acceptance']],
                ['Acceptance Rate', $stats['acceptance_rate'] . '%'],
                ['Notifications Enabled', $stats['notification_enabled'] ? 'Yes' : 'No'],
                ['Force Re-acceptance', $stats['force_reacceptance'] ? 'Yes' : 'No']
            ]
        );

        if ($stats['users_needing_acceptance'] > 0) {
            $this->warn("⚠️  {$stats['users_needing_acceptance']} users need to accept the current terms.");
        } else {
            $this->info('✅ All active users have accepted the current terms.');
        }
    }
}
