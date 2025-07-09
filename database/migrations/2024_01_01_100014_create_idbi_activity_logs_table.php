<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the idbi_activity_logs table for comprehensive activity logging
     * and audit trails in the Analytics Hub system.
     */
    public function up(): void
    {
        Schema::create('idbi_activity_logs', function (Blueprint $table) {
            // Primary key as UUID
            $table->uuid('id')->primary();
            
            // Activity identification
            $table->string('log_name', 50)->nullable(); // Log category/name
            $table->text('description'); // Human-readable description
            $table->string('event', 50); // Event type (created, updated, deleted, etc.)
            $table->string('action', 100); // Specific action performed
            
            // Subject (what was acted upon)
            $table->string('subject_type', 100)->nullable(); // Model class name
            $table->uuid('subject_id')->nullable(); // Model ID
            $table->json('subject_data')->nullable(); // Subject data snapshot
            
            // Causer (who performed the action)
            $table->string('causer_type', 100)->nullable(); // Usually 'App\Models\User'
            $table->uuid('causer_id')->nullable(); // User ID who performed action
            $table->json('causer_data')->nullable(); // Causer data snapshot
            
            // Activity details
            $table->json('properties')->nullable(); // Additional activity properties
            $table->json('old_values')->nullable(); // Old values (for updates)
            $table->json('new_values')->nullable(); // New values (for updates)
            $table->json('changes')->nullable(); // Summary of changes
            
            // Request context
            $table->string('ip_address', 45)->nullable(); // IP address
            $table->text('user_agent')->nullable(); // User agent
            $table->string('request_method', 10)->nullable(); // HTTP method
            $table->string('request_url', 500)->nullable(); // Request URL
            $table->json('request_data')->nullable(); // Request parameters
            
            // Session and authentication
            $table->string('session_id', 255)->nullable(); // Session ID
            $table->string('auth_method', 30)->nullable(); // Authentication method
            $table->boolean('is_authenticated')->default(true); // Was user authenticated
            
            // Activity categorization
            $table->string('module', 50)->nullable(); // System module (users, reports, etc.)
            $table->string('category', 50)->nullable(); // Activity category
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('type', ['user', 'system', 'security', 'admin', 'api'])->default('user');
            
            // Risk and security
            $table->boolean('is_sensitive')->default(false); // Contains sensitive data
            $table->boolean('is_suspicious')->default(false); // Flagged as suspicious
            $table->integer('risk_score')->default(0); // Risk score (0-100)
            $table->json('security_flags')->nullable(); // Security-related flags
            
            // Performance tracking
            $table->integer('execution_time')->nullable(); // Execution time in milliseconds
            $table->integer('memory_usage')->nullable(); // Memory usage in bytes
            $table->integer('query_count')->nullable(); // Number of database queries
            
            // Geographic information
            $table->string('country', 100)->nullable(); // Country from IP
            $table->string('region', 100)->nullable(); // Region/state from IP
            $table->string('city', 100)->nullable(); // City from IP
            
            // Device information
            $table->string('device_type', 50)->nullable(); // Device type
            $table->string('browser', 50)->nullable(); // Browser name
            $table->string('platform', 50)->nullable(); // Operating system
            
            // Batch and correlation
            $table->uuid('batch_id')->nullable(); // Batch ID for related activities
            $table->uuid('correlation_id')->nullable(); // Correlation ID for tracking
            $table->uuid('parent_activity_id')->nullable(); // Parent activity (for nested actions)
            
            // Status and lifecycle
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('completed');
            $table->text('error_message')->nullable(); // Error message (if failed)
            $table->json('error_details')->nullable(); // Detailed error information
            
            // Timestamps
            $table->timestamp('started_at')->nullable(); // When activity started
            $table->timestamp('completed_at')->nullable(); // When activity completed
            $table->timestamps(); // created_at, updated_at
            
            // Indexes for performance and querying
            $table->index(['log_name', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index(['causer_type', 'causer_id']);
            $table->index(['event', 'created_at']);
            $table->index(['module', 'category']);
            $table->index(['type', 'severity']);
            $table->index(['is_suspicious', 'created_at']);
            $table->index(['risk_score']);
            $table->index(['ip_address', 'created_at']);
            $table->index(['session_id']);
            $table->index(['batch_id']);
            $table->index(['correlation_id']);
            $table->index(['parent_activity_id']);
            $table->index(['status']);
            $table->index(['started_at']);
            $table->index(['completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_activity_logs');
    }
};