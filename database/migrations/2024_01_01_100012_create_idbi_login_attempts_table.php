<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the idbi_login_attempts table for tracking login attempts
     * and security monitoring in the Analytics Hub system.
     */
    public function up(): void
    {
        Schema::create('idbi_login_attempts', function (Blueprint $table) {
            // Primary key as UUID
            $table->uuid('id')->primary();
            
            // User identification
            $table->uuid('user_id')->nullable(); // User ID (null for failed attempts with invalid email)
            $table->string('email', 255); // Email used in login attempt
            $table->string('username', 50)->nullable(); // Username used (if applicable)
            
            // Attempt details
            $table->enum('status', ['success', 'failed', 'blocked', 'suspicious']); // Attempt result
            $table->string('failure_reason', 100)->nullable(); // Reason for failure
            $table->timestamp('attempted_at'); // When attempt was made
            
            // Request information
            $table->string('ip_address', 45); // IP address of attempt
            $table->text('user_agent')->nullable(); // User agent string
            $table->string('device_type', 50)->nullable(); // Device type (mobile, desktop, tablet)
            $table->string('browser', 50)->nullable(); // Browser name
            $table->string('platform', 50)->nullable(); // Operating system
            
            // Geographic information
            $table->string('country', 100)->nullable(); // Country from IP
            $table->string('region', 100)->nullable(); // Region/state from IP
            $table->string('city', 100)->nullable(); // City from IP
            $table->decimal('latitude', 10, 8)->nullable(); // Latitude
            $table->decimal('longitude', 11, 8)->nullable(); // Longitude
            
            // Security analysis
            $table->boolean('is_suspicious')->default(false); // Marked as suspicious
            $table->integer('risk_score')->default(0); // Risk score (0-100)
            $table->json('risk_factors')->nullable(); // Factors contributing to risk
            $table->boolean('is_blocked')->default(false); // IP/user blocked
            
            // Session information
            $table->string('session_id', 255)->nullable(); // Session ID if successful
            $table->boolean('remember_me')->default(false); // Remember me checkbox
            $table->string('two_factor_method', 20)->nullable(); // 2FA method used
            $table->boolean('two_factor_success')->nullable(); // 2FA success status
            
            // Rate limiting
            $table->integer('attempts_count')->default(1); // Number of attempts from this IP
            $table->timestamp('first_attempt_at')->nullable(); // First attempt in current window
            $table->timestamp('last_attempt_at')->nullable(); // Last attempt from this IP
            $table->timestamp('blocked_until')->nullable(); // Blocked until timestamp
            
            // Additional tracking
            $table->string('referrer', 500)->nullable(); // HTTP referrer
            $table->json('request_headers')->nullable(); // Important request headers
            $table->string('login_method', 30)->default('form'); // form, api, sso, etc.
            $table->string('client_id', 100)->nullable(); // API client ID (if applicable)
            
            // Response information
            $table->integer('response_time')->nullable(); // Response time in milliseconds
            $table->string('redirect_url', 500)->nullable(); // Redirect URL after login
            $table->json('response_data')->nullable(); // Additional response data
            
            // Audit fields
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('idbi_users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('idbi_users')->onDelete('set null');
            
            // Indexes for performance and security analysis
            $table->index(['user_id', 'attempted_at']);
            $table->index(['email', 'attempted_at']);
            $table->index(['ip_address', 'attempted_at']);
            $table->index(['status', 'attempted_at']);
            $table->index(['is_suspicious', 'attempted_at']);
            $table->index(['is_blocked']);
            $table->index(['risk_score']);
            $table->index(['country', 'attempted_at']);
            $table->index(['device_type', 'browser']);
            $table->index(['blocked_until']);
            $table->index(['login_method']);
            $table->index(['created_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_login_attempts');
    }
};