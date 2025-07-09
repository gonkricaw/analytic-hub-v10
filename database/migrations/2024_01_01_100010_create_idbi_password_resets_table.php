<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the idbi_password_resets table for managing password reset tokens
     * in the Analytics Hub system.
     */
    public function up(): void
    {
        Schema::create('idbi_password_resets', function (Blueprint $table) {
            // Primary key as UUID
            $table->uuid('id')->primary();
            
            // User identification
            $table->uuid('user_id'); // User requesting password reset
            $table->string('email', 255); // Email address (for verification)
            
            // Reset token information
            $table->string('token', 255)->unique(); // Reset token (hashed)
            $table->string('token_hash', 255); // Additional token hash for security
            $table->timestamp('expires_at'); // Token expiration time
            
            // Reset request details
            $table->string('ip_address', 45)->nullable(); // IP address of request
            $table->text('user_agent')->nullable(); // User agent of request
            $table->string('request_method', 20)->default('email'); // How reset was requested
            
            // Reset status tracking
            $table->enum('status', ['pending', 'used', 'expired', 'revoked'])->default('pending');
            $table->timestamp('used_at')->nullable(); // When token was used
            $table->timestamp('revoked_at')->nullable(); // When token was revoked
            $table->text('revocation_reason')->nullable(); // Reason for revocation
            
            // Security tracking
            $table->integer('attempt_count')->default(0); // Number of reset attempts
            $table->timestamp('last_attempt_at')->nullable(); // Last reset attempt
            $table->json('attempt_ips')->nullable(); // IPs that attempted to use token
            $table->boolean('is_suspicious')->default(false); // Suspicious activity flag
            
            // Notification tracking
            $table->boolean('email_sent')->default(false); // Reset email sent status
            $table->timestamp('email_sent_at')->nullable(); // When reset email was sent
            $table->boolean('confirmation_sent')->default(false); // Confirmation email sent
            $table->timestamp('confirmation_sent_at')->nullable(); // When confirmation was sent
            
            // Additional security measures
            $table->string('verification_code', 10)->nullable(); // Additional verification code
            $table->boolean('requires_verification')->default(false); // Requires additional verification
            $table->json('security_questions')->nullable(); // Security questions (if applicable)
            $table->json('security_answers')->nullable(); // Hashed security answers
            
            // Audit fields
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('idbi_users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('idbi_users')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['email', 'status']);
            $table->index(['token']);
            $table->index(['user_id', 'status']);
            $table->index(['expires_at', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['ip_address']);
            $table->index(['is_suspicious']);
            $table->index(['created_by']);
            $table->index(['updated_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_password_resets');
    }
};