<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for creating content_access_logs table
 * 
 * This migration creates the content_access_logs table to support:
 * - Security auditing for encrypted embedded content
 * - Access tracking and monitoring
 * - User behavior analytics
 * - Compliance and security reporting
 * 
 * @author Analytics Hub Team
 * @version 1.0
 * @since 2024-01-01
 */
class CreateContentAccessLogsTable extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the content_access_logs table to track access to content,
     * especially for encrypted embedded reports and security monitoring.
     * 
     * @return void
     */
    public function up(): void
    {
        Schema::create('content_access_logs', function (Blueprint $table) {
            // Primary key and timestamps
            $table->id();
            $table->timestamps();
            
            // Content reference
            $table->unsignedBigInteger('content_id');
            
            // User information
            $table->unsignedBigInteger('user_id')->nullable(); // Nullable for anonymous access
            $table->string('user_email')->nullable(); // For tracking even if user is deleted
            $table->string('user_role')->nullable(); // Role at time of access
            
            // Access details
            $table->enum('access_type', [
                'view',           // Regular content view
                'embed',          // Embedded content access
                'secure_view',    // Secure token-based access
                'download',       // Content download
                'share',          // Content sharing
                'token_generate', // Access token generation
                'token_use'       // Access token usage
            ]);
            
            // Security and tracking information
            $table->string('ip_address', 45); // IPv4 and IPv6 support
            $table->text('user_agent')->nullable();
            $table->string('session_id')->nullable();
            $table->string('access_token')->nullable(); // For token-based access
            $table->timestamp('token_expires_at')->nullable();
            
            // Request details
            $table->string('referer')->nullable();
            $table->string('request_method', 10)->default('GET');
            $table->json('request_headers')->nullable(); // Selected security headers
            $table->json('request_params')->nullable(); // Query parameters
            
            // Geographic and device information
            $table->string('country_code', 2)->nullable();
            $table->string('city')->nullable();
            $table->string('device_type')->nullable(); // mobile, desktop, tablet
            $table->string('browser')->nullable();
            $table->string('platform')->nullable(); // OS
            
            // Content-specific information
            $table->string('content_uuid')->nullable(); // For encrypted content
            $table->string('content_type')->nullable(); // custom, embedded
            $table->string('content_title')->nullable(); // Snapshot of title
            
            // Access result and security
            $table->enum('access_result', [
                'success',        // Successful access
                'denied',         // Access denied
                'expired',        // Token/content expired
                'invalid_token',  // Invalid access token
                'rate_limited',   // Rate limit exceeded
                'blocked',        // IP/user blocked
                'error'           // System error
            ]);
            
            $table->string('denial_reason')->nullable(); // Reason for access denial
            $table->text('error_message')->nullable(); // Error details if any
            
            // Performance and analytics
            $table->unsignedInteger('response_time_ms')->nullable(); // Response time in milliseconds
            $table->unsignedBigInteger('bytes_transferred')->nullable(); // Data transferred
            $table->unsignedInteger('session_duration')->nullable(); // Time spent viewing (seconds)
            
            // Security flags
            $table->boolean('is_suspicious')->default(false); // Flagged as suspicious
            $table->boolean('is_bot')->default(false); // Detected as bot traffic
            $table->boolean('is_vpn')->default(false); // VPN/proxy detected
            $table->boolean('is_tor')->default(false); // Tor network detected
            
            // Additional metadata
            $table->json('metadata')->nullable(); // Additional tracking data
            $table->text('notes')->nullable(); // Manual notes for investigation
            
            // Indexes for performance and querying
            $table->index(['content_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['access_type', 'created_at']);
            $table->index(['access_result']);
            $table->index(['ip_address']);
            $table->index(['access_token']);
            $table->index(['content_uuid']);
            $table->index(['is_suspicious']);
            $table->index(['created_at']); // For time-based queries
            $table->index(['country_code']);
            $table->index(['device_type']);
            
            // Composite indexes for common queries
            $table->index(['content_id', 'access_type', 'created_at']);
            $table->index(['user_id', 'access_type', 'created_at']);
            $table->index(['ip_address', 'created_at']);
            
            // Foreign key constraints
            $table->foreign('content_id')->references('id')->on('contents')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Drops the content_access_logs table and all associated indexes and constraints.
     * 
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('content_access_logs');
    }
}