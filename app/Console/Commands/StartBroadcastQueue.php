<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Class StartBroadcastQueue
 * 
 * Console command to start the queue worker specifically for broadcasting events.
 * This command ensures that broadcast events are processed in real-time.
 * 
 * Usage: php artisan broadcast:start
 * 
 * @package App\Console\Commands
 * @author Analytics Hub Team
 * @version 1.0.0
 */
class StartBroadcastQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'broadcast:start 
                            {--queue=default : The queue to process}
                            {--timeout=60 : The timeout for each job}
                            {--memory=128 : The memory limit in MB}
                            {--tries=3 : Number of attempts for failed jobs}
                            {--delay=0 : Delay failed jobs for this many seconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the queue worker for processing broadcast events';

    /**
     * Execute the console command.
     *
     * This method starts a queue worker specifically configured for
     * processing broadcast events with optimized settings.
     *
     * @return int Command exit code
     */
    public function handle(): int
    {
        $this->info('Starting broadcast queue worker...');
        
        // Get command options
        $queue = $this->option('queue');
        $timeout = $this->option('timeout');
        $memory = $this->option('memory');
        $tries = $this->option('tries');
        $delay = $this->option('delay');
        
        // Display configuration
        $this->table(
            ['Setting', 'Value'],
            [
                ['Queue', $queue],
                ['Timeout', $timeout . ' seconds'],
                ['Memory Limit', $memory . ' MB'],
                ['Max Tries', $tries],
                ['Delay on Failure', $delay . ' seconds'],
            ]
        );
        
        $this->info('Press Ctrl+C to stop the worker.');
        $this->newLine();
        
        try {
            // Start the queue worker with broadcast-optimized settings
            Artisan::call('queue:work', [
                '--queue' => $queue,
                '--timeout' => $timeout,
                '--memory' => $memory,
                '--tries' => $tries,
                '--delay' => $delay,
                '--verbose' => true,
                '--rest' => 1, // Rest 1 second between jobs
            ]);
            
            // Output the result
            $this->info(Artisan::output());
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Failed to start broadcast queue worker: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}