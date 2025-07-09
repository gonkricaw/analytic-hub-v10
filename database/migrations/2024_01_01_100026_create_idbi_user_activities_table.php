<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the idbi_user_activities table for comprehensive user activity tracking
     * in the Analytics Hub system.
     */
    public function up(): void
    {
        Schema::create('idbi_user_activities', function (Blueprint $table) {
            // Primary key
            $table->uuid('id')->primary();
            
            // User identification
            $table->uuid('user_id')->nullable(); // User performing activity
            $table->string('user_email', 255)->nullable(); // User email at time of activity
            $table->string('user_name', 255)->nullable(); // User name at time of activity
            $table->uuid('impersonated_by')->nullable(); // If user was impersonated
            
            // Activity identification
            $table->string('activity_type', 100); // Type of activity
            $table->string('activity_name', 255); // Activity name/description
            $table->string('event', 100)->nullable(); // Specific event
            $table->string('action', 100)->nullable(); // Action performed
            $table->text('description')->nullable(); // Activity description
            
            // Subject and object
            $table->string('subject_type', 255)->nullable(); // Subject model type
            $table->uuid('subject_id')->nullable(); // Subject model ID
            $table->string('subject_name', 255)->nullable(); // Subject name/title
            $table->string('causer_type', 255)->nullable(); // Causer model type
            $table->uuid('causer_id')->nullable(); // Causer model ID
            
            // Activity properties
            $table->json('properties')->nullable(); // Activity properties
            $table->json('old_values')->nullable(); // Old values (for updates)
            $table->json('new_values')->nullable(); // New values (for updates)
            $table->json('changes')->nullable(); // Changed fields
            $table->json('attributes')->nullable(); // Additional attributes
            
            // Request context
            $table->string('ip_address', 45)->nullable(); // IP address
            $table->string('user_agent', 1000)->nullable(); // User agent
            $table->string('url', 1000)->nullable(); // Request URL
            $table->string('method', 10)->nullable(); // HTTP method
            $table->json('request_data')->nullable(); // Request data
            $table->json('response_data')->nullable(); // Response data
            $table->integer('response_code')->nullable(); // HTTP response code
            
            // Session and authentication
            $table->string('session_id', 255)->nullable(); // Session ID
            $table->string('csrf_token', 255)->nullable(); // CSRF token
            $table->boolean('is_authenticated')->default(true); // Authentication status
            $table->string('auth_method', 50)->nullable(); // Authentication method
            $table->json('auth_data')->nullable(); // Authentication details
            
            // Categorization
            $table->string('module', 100)->nullable(); // System module
            $table->string('category', 100)->nullable(); // Activity category
            $table->enum('severity', ['info', 'warning', 'error', 'critical'])->default('info');
            $table->enum('type', ['create', 'read', 'update', 'delete', 'login', 'logout', 'other'])->default('other');
            
            // Risk and security
            $table->boolean('is_sensitive')->default(false); // Sensitive activity
            $table->boolean('is_suspicious')->default(false); // Suspicious activity
            $table->integer('risk_score')->nullable(); // Risk score (0-100)
            $table->json('risk_factors')->nullable(); // Risk assessment factors
            $table->boolean('requires_review')->default(false); // Needs manual review
            
            // Performance tracking
            $table->integer('execution_time_ms')->nullable(); // Execution time
            $table->integer('memory_usage_mb')->nullable(); // Memory usage
            $table->integer('query_count')->nullable(); // Database queries
            $table->json('performance_data')->nullable(); // Performance metrics
            
            // Geographic and device information
            $table->string('country', 100)->nullable(); // Country
            $table->string('region', 100)->nullable(); // Region/state
            $table->string('city', 100)->nullable(); // City
            $table->decimal('latitude', 10, 8)->nullable(); // Latitude
            $table->decimal('longitude', 11, 8)->nullable(); // Longitude
            $table->string('timezone', 50)->nullable(); // Timezone
            
            // Device information
            $table->string('device_type', 50)->nullable(); // Device type
            $table->string('device_name', 100)->nullable(); // Device name
            $table->string('browser', 100)->nullable(); // Browser name
            $table->string('browser_version', 50)->nullable(); // Browser version
            $table->string('platform', 100)->nullable(); // Operating system
            $table->string('platform_version', 50)->nullable(); // OS version
            $table->json('device_info')->nullable(); // Additional device info
            
            // Batch and correlation
            $table->string('batch_id', 255)->nullable(); // Batch identifier
            $table->string('correlation_id', 255)->nullable(); // Correlation ID
            $table->string('trace_id', 255)->nullable(); // Trace ID
            $table->string('parent_activity_id', 255)->nullable(); // Parent activity
            $table->json('related_activities')->nullable(); // Related activity IDs
            
            // Status and lifecycle
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('completed');
            $table->text('status_message')->nullable(); // Status description
            $table->timestamp('started_at')->nullable(); // Activity start time
            $table->timestamp('completed_at')->nullable(); // Activity completion time
            $table->integer('duration_ms')->nullable(); // Activity duration
            
            // Error handling
            $table->text('error_message')->nullable(); // Error message
            $table->longText('error_details')->nullable(); // Error details
            $table->longText('stack_trace')->nullable(); // Error stack trace
            $table->string('error_code', 50)->nullable(); // Error code
            $table->json('error_context')->nullable(); // Error context
            
            // Notification and alerting
            $table->boolean('notify_admin')->default(false); // Notify administrators
            $table->boolean('alert_sent')->default(false); // Alert notification sent
            $table->timestamp('alert_sent_at')->nullable(); // Alert timestamp
            $table->json('alert_recipients')->nullable(); // Alert recipients
            
            // Data retention and archival
            $table->boolean('is_archived')->default(false); // Archived status
            $table->timestamp('archived_at')->nullable(); // Archive timestamp
            $table->timestamp('expires_at')->nullable(); // Expiration time
            $table->boolean('can_be_deleted')->default(true); // Deletion allowed
            
            // Compliance and audit
            $table->boolean('is_audit_required')->default(false); // Audit requirement
            $table->boolean('is_compliance_relevant')->default(false); // Compliance relevance
            $table->json('compliance_data')->nullable(); // Compliance information
            $table->text('audit_notes')->nullable(); // Audit notes
            
            // Tags and metadata
            $table->json('tags')->nullable(); // Activity tags
            $table->json('metadata')->nullable(); // Additional metadata
            $table->json('custom_fields')->nullable(); // Custom fields
            $table->text('notes')->nullable(); // Activity notes
            
            // Audit fields
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('impersonated_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('idbi_users')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['user_id', 'created_at']);
            $table->index(['activity_type', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index(['causer_type', 'causer_id']);
            $table->index(['ip_address', 'created_at']);
            $table->index(['session_id']);
            $table->index(['module', 'category']);
            $table->index(['severity', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index(['is_sensitive']);
            $table->index(['is_suspicious']);
            $table->index(['risk_score']);
            $table->index(['requires_review']);
            $table->index(['status']);
            $table->index(['batch_id']);
            $table->index(['correlation_id']);
            $table->index(['is_archived']);
            $table->index(['expires_at']);
            $table->index(['created_by']);
            $table->index(['updated_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_user_activities');
    }
};