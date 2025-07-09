<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the idbi_failed_jobs table for enhanced failed job tracking
     * in the Analytics Hub system.
     */
    public function up(): void
    {
        Schema::create('idbi_failed_jobs', function (Blueprint $table) {
            // Primary key
            $table->id();
            
            // Job identification
            $table->string('uuid', 36)->unique(); // Job UUID
            $table->text('connection'); // Queue connection
            $table->text('queue'); // Queue name
            $table->longText('payload'); // Job payload
            $table->longText('exception'); // Exception details
            $table->timestamp('failed_at')->useCurrent(); // Failure timestamp
            
            // Enhanced job metadata
            $table->string('job_class', 255)->nullable(); // Job class name
            $table->string('job_method', 100)->nullable(); // Job method
            $table->string('job_name', 255)->nullable(); // Human-readable job name
            $table->text('job_description')->nullable(); // Job description
            
            // Job categorization
            $table->string('job_type', 50)->default('general'); // Job type
            $table->string('category', 50)->nullable(); // Job category
            $table->string('module', 50)->nullable(); // System module
            $table->enum('priority', ['low', 'normal', 'high', 'critical'])->default('normal');
            
            // Failure analysis
            $table->string('failure_type', 100)->nullable(); // Type of failure
            $table->string('error_code', 50)->nullable(); // Error code
            $table->text('error_message')->nullable(); // Error message
            $table->longText('stack_trace')->nullable(); // Stack trace
            $table->json('error_context')->nullable(); // Error context data
            
            // Job execution history
            $table->integer('total_attempts')->default(0); // Total attempts made
            $table->integer('max_attempts')->default(3); // Maximum attempts allowed
            $table->timestamp('first_attempted_at')->nullable(); // First attempt time
            $table->timestamp('last_attempted_at')->nullable(); // Last attempt time
            $table->json('attempt_history')->nullable(); // History of all attempts
            
            // Job timing
            $table->timestamp('originally_queued_at')->nullable(); // Original queue time
            $table->timestamp('started_at')->nullable(); // Job start time
            $table->integer('execution_time_ms')->nullable(); // Execution time before failure
            $table->integer('timeout_seconds')->nullable(); // Job timeout setting
            
            // Job context
            $table->uuid('user_id')->nullable(); // User who initiated job
            $table->string('session_id', 255)->nullable(); // Session ID
            $table->string('ip_address', 45)->nullable(); // IP address
            $table->json('request_data')->nullable(); // Original request data
            $table->json('context_data')->nullable(); // Additional context
            
            // System information
            $table->string('server_id', 100)->nullable(); // Server ID
            $table->string('worker_id', 100)->nullable(); // Worker ID
            $table->string('process_id', 100)->nullable(); // Process ID
            $table->string('php_version', 20)->nullable(); // PHP version
            $table->json('system_info')->nullable(); // System information
            
            // Performance data
            $table->integer('memory_usage_mb')->nullable(); // Memory usage at failure
            $table->integer('cpu_usage_percent')->nullable(); // CPU usage at failure
            $table->json('performance_metrics')->nullable(); // Performance metrics
            
            // Job dependencies
            $table->string('batch_id', 255)->nullable(); // Batch ID
            $table->string('parent_job_id', 255)->nullable(); // Parent job ID
            $table->json('dependent_jobs')->nullable(); // Jobs that depend on this
            $table->json('dependency_failures')->nullable(); // Failed dependencies
            
            // Recovery and retry
            $table->boolean('is_retryable')->default(true); // Can be retried
            $table->timestamp('retry_after')->nullable(); // Earliest retry time
            $table->json('retry_strategy')->nullable(); // Retry strategy
            $table->integer('retry_count')->default(0); // Number of retries attempted
            $table->timestamp('last_retry_at')->nullable(); // Last retry attempt
            
            // Resolution tracking
            $table->enum('resolution_status', [
                'unresolved', 'investigating', 'resolved', 'ignored', 'permanent_failure'
            ])->default('unresolved');
            $table->text('resolution_notes')->nullable(); // Resolution notes
            $table->uuid('resolved_by')->nullable(); // Who resolved the issue
            $table->timestamp('resolved_at')->nullable(); // Resolution timestamp
            
            // Notification tracking
            $table->boolean('notifications_sent')->default(false); // Notifications sent
            $table->json('notification_recipients')->nullable(); // Who was notified
            $table->timestamp('notified_at')->nullable(); // Notification timestamp
            
            // Impact assessment
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->text('business_impact')->nullable(); // Business impact description
            $table->json('affected_users')->nullable(); // Affected users/systems
            $table->boolean('requires_immediate_attention')->default(false);
            
            // Archival and cleanup
            $table->boolean('is_archived')->default(false); // Archived flag
            $table->timestamp('archived_at')->nullable(); // Archive timestamp
            $table->text('archive_reason')->nullable(); // Archive reason
            $table->boolean('can_be_deleted')->default(false); // Safe to delete
            
            // Audit fields
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('resolved_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('idbi_users')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['failed_at']);
            $table->index(['job_class', 'failed_at']);
            $table->index(['job_type', 'failed_at']);
            $table->index(['priority', 'failed_at']);
            $table->index(['user_id', 'failed_at']);
            $table->index(['resolution_status']);
            $table->index(['severity', 'failed_at']);
            $table->index(['is_retryable', 'retry_after']);
            $table->index(['batch_id']);
            $table->index(['parent_job_id']);
            $table->index(['requires_immediate_attention']);
            $table->index(['is_archived']);
            $table->index(['can_be_deleted']);
            $table->index(['created_by']);
            $table->index(['updated_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_failed_jobs');
    }
};