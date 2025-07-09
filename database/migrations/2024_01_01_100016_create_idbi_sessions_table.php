<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the idbi_sessions table for enhanced session management
     * in the Analytics Hub system.
     */
    public function up(): void
    {
        Schema::create('idbi_sessions', function (Blueprint $table) {
            // Primary key
            $table->string('id', 255)->primary(); // Session ID
            
            // User identification
            $table->uuid('user_id')->nullable(); // Associated user
            $table->string('user_email', 255)->nullable(); // User email for quick lookup
            
            // Session data
            $table->longText('payload'); // Session payload data
            $table->integer('last_activity')->index(); // Last activity timestamp
            
            // Request information
            $table->string('ip_address', 45)->nullable(); // Client IP address
            $table->text('user_agent')->nullable(); // User agent string
            $table->string('device_type', 50)->nullable(); // Device type (mobile, desktop, tablet)
            $table->string('browser', 50)->nullable(); // Browser name
            $table->string('platform', 50)->nullable(); // Operating system
            
            // Session metadata
            $table->timestamp('created_at'); // Session creation time
            $table->timestamp('expires_at')->nullable(); // Session expiration time
            $table->boolean('is_active')->default(true); // Session status
            $table->boolean('is_authenticated')->default(false); // Authentication status
            
            // Security tracking
            $table->boolean('is_suspicious')->default(false); // Suspicious activity flag
            $table->integer('risk_score')->default(0); // Risk score (0-100)
            $table->json('security_flags')->nullable(); // Security-related flags
            $table->timestamp('last_security_check')->nullable(); // Last security validation
            
            // Geographic information
            $table->string('country', 100)->nullable(); // Country from IP
            $table->string('region', 100)->nullable(); // Region/state from IP
            $table->string('city', 100)->nullable(); // City from IP
            $table->decimal('latitude', 10, 8)->nullable(); // Latitude
            $table->decimal('longitude', 11, 8)->nullable(); // Longitude
            
            // Session behavior
            $table->boolean('remember_token')->default(false); // Remember me session
            $table->integer('idle_timeout')->nullable(); // Idle timeout in seconds
            $table->integer('max_lifetime')->nullable(); // Maximum session lifetime
            $table->timestamp('last_seen_at')->nullable(); // Last user activity
            
            // Multi-device management
            $table->string('device_fingerprint', 255)->nullable(); // Device fingerprint
            $table->boolean('is_mobile')->default(false); // Mobile device flag
            $table->boolean('allow_concurrent')->default(true); // Allow concurrent sessions
            $table->integer('concurrent_count')->default(1); // Number of concurrent sessions
            
            // Session termination
            $table->enum('termination_reason', [
                'logout', 'timeout', 'expired', 'forced', 'security', 'admin', 'device_limit'
            ])->nullable(); // How session ended
            $table->timestamp('terminated_at')->nullable(); // When session was terminated
            $table->uuid('terminated_by')->nullable(); // Who terminated the session
            
            // Performance tracking
            $table->integer('page_views')->default(0); // Number of page views in session
            $table->integer('ajax_requests')->default(0); // Number of AJAX requests
            $table->integer('api_calls')->default(0); // Number of API calls
            $table->json('performance_metrics')->nullable(); // Performance data
            
            // Session validation
            $table->string('csrf_token', 255)->nullable(); // CSRF token
            $table->string('validation_hash', 255)->nullable(); // Session validation hash
            $table->timestamp('last_validated_at')->nullable(); // Last validation time
            $table->boolean('requires_revalidation')->default(false); // Needs revalidation
            
            // Audit fields
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            
            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('idbi_users')->onDelete('cascade');
            $table->foreign('terminated_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('idbi_users')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['user_id', 'is_active']);
            $table->index(['last_activity']);
            $table->index(['ip_address', 'created_at']);
            $table->index(['is_authenticated', 'is_active']);
            $table->index(['expires_at']);
            $table->index(['is_suspicious']);
            $table->index(['device_fingerprint']);
            $table->index(['country', 'created_at']);
            $table->index(['termination_reason']);
            $table->index(['last_seen_at']);
            $table->index(['created_by']);
            $table->index(['updated_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_sessions');
    }
};