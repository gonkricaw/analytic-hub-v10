<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the idbi_job_batches table for enhanced batch job management
     * in the Analytics Hub system.
     */
    public function up(): void
    {
        Schema::create('idbi_job_batches', function (Blueprint $table) {
            // Primary key
            $table->string('id', 255)->primary(); // Batch ID
            
            // Batch identification
            $table->string('name', 255); // Batch name
            $table->text('description')->nullable(); // Batch description
            $table->string('batch_type', 50)->default('general'); // Batch type
            $table->string('category', 50)->nullable(); // Batch category
            $table->string('module', 50)->nullable(); // System module
            
            // Batch statistics
            $table->integer('total_jobs'); // Total jobs in batch
            $table->integer('pending_jobs'); // Pending jobs count
            $table->integer('failed_jobs'); // Failed jobs count
            $table->text('failed_job_ids'); // Failed job IDs
            $table->json('options'); // Batch options
            $table->integer('cancelled_at')->nullable(); // Cancellation timestamp
            $table->timestamp('created_at'); // Creation timestamp
            $table->timestamp('finished_at')->nullable(); // Completion timestamp
            
            // Enhanced batch metadata
            $table->integer('processing_jobs')->default(0); // Currently processing
            $table->integer('completed_jobs')->default(0); // Completed jobs count
            $table->integer('retried_jobs')->default(0); // Retried jobs count
            $table->integer('skipped_jobs')->default(0); // Skipped jobs count
            
            // Batch status
            $table->enum('status', [
                'pending', 'processing', 'completed', 'failed', 'cancelled', 'paused'
            ])->default('pending');
            $table->text('status_message')->nullable(); // Status message
            $table->json('status_data')->nullable(); // Additional status data
            
            // Batch progress
            $table->decimal('progress_percentage', 5, 2)->default(0); // Progress percentage
            $table->text('progress_message')->nullable(); // Progress message
            $table->json('progress_data')->nullable(); // Progress details
            $table->timestamp('progress_updated_at')->nullable(); // Last progress update
            
            // Batch configuration
            $table->enum('priority', ['low', 'normal', 'high', 'critical'])->default('normal');
            $table->boolean('allow_failures')->default(false); // Allow some failures
            $table->integer('failure_threshold')->nullable(); // Max allowed failures
            $table->integer('timeout_seconds')->nullable(); // Batch timeout
            $table->json('retry_strategy')->nullable(); // Retry configuration
            
            // Batch scheduling
            $table->timestamp('scheduled_at')->nullable(); // Scheduled start time
            $table->timestamp('started_at')->nullable(); // Actual start time
            $table->timestamp('paused_at')->nullable(); // Pause timestamp
            $table->timestamp('resumed_at')->nullable(); // Resume timestamp
            $table->text('pause_reason')->nullable(); // Pause reason
            
            // Batch context
            $table->uuid('user_id')->nullable(); // User who created batch
            $table->string('session_id', 255)->nullable(); // Session ID
            $table->string('ip_address', 45)->nullable(); // IP address
            $table->json('context_data')->nullable(); // Additional context
            
            // Batch dependencies
            $table->string('parent_batch_id', 255)->nullable(); // Parent batch
            $table->json('child_batches')->nullable(); // Child batch IDs
            $table->json('dependencies')->nullable(); // Batch dependencies
            $table->json('dependent_batches')->nullable(); // Batches depending on this
            
            // Performance tracking
            $table->integer('total_execution_time_ms')->nullable(); // Total execution time
            $table->integer('average_job_time_ms')->nullable(); // Average job time
            $table->integer('peak_memory_usage_mb')->nullable(); // Peak memory usage
            $table->integer('total_memory_usage_mb')->nullable(); // Total memory used
            $table->json('performance_metrics')->nullable(); // Performance data
            
            // Resource management
            $table->integer('max_concurrent_jobs')->nullable(); // Max concurrent jobs
            $table->integer('current_concurrent_jobs')->default(0); // Current concurrent
            $table->string('queue_name', 100)->nullable(); // Target queue
            $table->json('resource_limits')->nullable(); // Resource constraints
            
            // Batch results
            $table->longText('result_summary')->nullable(); // Result summary
            $table->json('output_files')->nullable(); // Generated files
            $table->json('result_data')->nullable(); // Batch results
            $table->json('aggregated_metrics')->nullable(); // Aggregated metrics
            
            // Error handling
            $table->text('error_message')->nullable(); // Batch error message
            $table->longText('error_details')->nullable(); // Detailed errors
            $table->json('error_summary')->nullable(); // Error summary
            $table->integer('error_count')->default(0); // Total errors
            
            // Notification settings
            $table->boolean('notify_on_completion')->default(false); // Notify when done
            $table->boolean('notify_on_failure')->default(true); // Notify on failure
            $table->json('notification_recipients')->nullable(); // Who to notify
            $table->boolean('notifications_sent')->default(false); // Notifications sent
            $table->timestamp('notified_at')->nullable(); // Notification time
            
            // Batch lifecycle
            $table->boolean('is_restartable')->default(true); // Can be restarted
            $table->boolean('auto_cleanup')->default(true); // Auto cleanup on completion
            $table->timestamp('cleanup_after')->nullable(); // Cleanup timestamp
            $table->boolean('is_archived')->default(false); // Archived flag
            $table->timestamp('archived_at')->nullable(); // Archive timestamp
            
            // Monitoring and alerts
            $table->boolean('enable_monitoring')->default(true); // Enable monitoring
            $table->json('alert_thresholds')->nullable(); // Alert thresholds
            $table->json('monitoring_data')->nullable(); // Monitoring metrics
            $table->timestamp('last_monitored_at')->nullable(); // Last monitoring check
            
            // Audit fields
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            
            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('idbi_users')->onDelete('set null');
            // Note: parent_batch_id self-reference removed due to PostgreSQL constraint issues
            $table->foreign('created_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('idbi_users')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['status', 'created_at']);
            $table->index(['batch_type', 'status']);
            $table->index(['priority', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['parent_batch_id']);
            $table->index(['scheduled_at']);
            $table->index(['started_at']);
            $table->index(['finished_at']);
            $table->index(['cancelled_at']);
            $table->index(['is_archived']);
            $table->index(['enable_monitoring']);
            $table->index(['created_by']);
            $table->index(['updated_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_job_batches');
    }
};