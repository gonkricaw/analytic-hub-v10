<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the idbi_jobs table for enhanced job queue management
     * in the Analytics Hub system.
     */
    public function up(): void
    {
        Schema::create('idbi_jobs', function (Blueprint $table) {
            // Primary key
            $table->bigIncrements('id');
            
            // Job identification
            $table->string('queue', 100)->index(); // Queue name
            $table->longText('payload'); // Job payload
            $table->unsignedTinyInteger('attempts')->default(0); // Attempt count
            $table->unsignedInteger('reserved_at')->nullable(); // Reserved timestamp
            $table->unsignedInteger('available_at'); // Available timestamp
            $table->unsignedInteger('created_at'); // Created timestamp
            
            // Job metadata
            $table->string('job_class', 255)->nullable(); // Job class name
            $table->string('job_method', 100)->nullable(); // Job method
            $table->string('job_name', 255)->nullable(); // Human-readable job name
            $table->text('job_description')->nullable(); // Job description
            
            // Job categorization
            $table->string('job_type', 50)->default('general'); // Job type
            $table->string('category', 50)->nullable(); // Job category
            $table->string('module', 50)->nullable(); // System module
            $table->enum('priority', ['low', 'normal', 'high', 'critical'])->default('normal');
            
            // Job scheduling
            $table->timestamp('scheduled_at')->nullable(); // Scheduled execution time
            $table->timestamp('started_at')->nullable(); // Job start time
            $table->timestamp('completed_at')->nullable(); // Job completion time
            $table->timestamp('failed_at')->nullable(); // Job failure time
            
            // Job status
            $table->enum('status', [
                'pending', 'processing', 'completed', 'failed', 'cancelled', 'retrying'
            ])->default('pending');
            $table->text('status_message')->nullable(); // Status message
            $table->json('status_data')->nullable(); // Additional status data
            
            // Job progress
            $table->integer('progress_current')->default(0); // Current progress
            $table->integer('progress_total')->default(100); // Total progress
            $table->decimal('progress_percentage', 5, 2)->default(0); // Progress percentage
            $table->text('progress_message')->nullable(); // Progress message
            
            // Job configuration
            $table->integer('max_attempts')->default(3); // Maximum attempts
            $table->integer('timeout')->nullable(); // Job timeout (seconds)
            $table->integer('retry_delay')->nullable(); // Retry delay (seconds)
            $table->json('retry_strategy')->nullable(); // Retry strategy configuration
            
            // Job context
            $table->uuid('user_id')->nullable(); // User who initiated job
            $table->string('session_id', 255)->nullable(); // Session ID
            $table->string('ip_address', 45)->nullable(); // IP address
            $table->json('context_data')->nullable(); // Additional context
            
            // Job dependencies
            $table->json('dependencies')->nullable(); // Job dependencies
            $table->string('batch_id', 255)->nullable(); // Batch ID
            $table->string('parent_job_id', 255)->nullable(); // Parent job ID
            $table->json('child_jobs')->nullable(); // Child job IDs
            
            // Job results
            $table->longText('result_data')->nullable(); // Job result data
            $table->json('output_files')->nullable(); // Generated files
            $table->json('metrics')->nullable(); // Performance metrics
            $table->text('error_message')->nullable(); // Error message
            $table->longText('error_details')->nullable(); // Detailed error information
            $table->longText('stack_trace')->nullable(); // Error stack trace
            
            // Performance tracking
            $table->integer('execution_time_ms')->nullable(); // Execution time (ms)
            $table->integer('memory_usage_mb')->nullable(); // Memory usage (MB)
            $table->integer('cpu_usage_percent')->nullable(); // CPU usage percentage
            $table->json('performance_data')->nullable(); // Additional performance data
            
            // Job worker information
            $table->string('worker_id', 100)->nullable(); // Worker ID
            $table->string('server_id', 100)->nullable(); // Server ID
            $table->string('process_id', 100)->nullable(); // Process ID
            $table->string('worker_class', 255)->nullable(); // Worker class
            
            // Job notifications
            $table->boolean('notify_on_success')->default(false); // Notify on success
            $table->boolean('notify_on_failure')->default(true); // Notify on failure
            $table->json('notification_recipients')->nullable(); // Notification recipients
            $table->boolean('notifications_sent')->default(false); // Notifications sent flag
            
            // Job archival
            $table->boolean('is_archived')->default(false); // Archived flag
            $table->timestamp('archived_at')->nullable(); // Archive timestamp
            $table->text('archive_reason')->nullable(); // Archive reason
            
            // Audit fields
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            
            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('idbi_users')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['queue', 'status']);
            $table->index(['status', 'available_at']);
            $table->index(['priority', 'available_at']);
            $table->index(['job_type', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['batch_id']);
            $table->index(['parent_job_id']);
            $table->index(['scheduled_at']);
            $table->index(['started_at']);
            $table->index(['completed_at']);
            $table->index(['failed_at']);
            $table->index(['is_archived']);
            $table->index(['created_by']);
            $table->index(['updated_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_jobs');
    }
};