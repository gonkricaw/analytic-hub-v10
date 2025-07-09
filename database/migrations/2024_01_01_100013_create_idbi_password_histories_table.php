<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the idbi_password_histories table for tracking password history
     * to enforce password reuse policies in the Analytics Hub system.
     */
    public function up(): void
    {
        Schema::create('idbi_password_histories', function (Blueprint $table) {
            // Primary key as UUID
            $table->uuid('id')->primary();
            
            // User identification
            $table->uuid('user_id'); // User whose password this belongs to
            
            // Password information
            $table->string('password_hash'); // Hashed password
            $table->string('hash_algorithm', 20)->default('bcrypt'); // Hashing algorithm used
            $table->integer('hash_cost')->nullable(); // Hash cost/rounds (for bcrypt)
            
            // Password metadata
            $table->timestamp('created_at'); // When password was set
            $table->timestamp('expires_at')->nullable(); // Password expiration (if applicable)
            $table->boolean('is_current')->default(false); // Current active password
            $table->boolean('was_forced_change')->default(false); // Was a forced password change
            
            // Password strength analysis
            $table->integer('strength_score')->nullable(); // Password strength score (0-100)
            $table->json('strength_analysis')->nullable(); // Detailed strength analysis
            $table->boolean('meets_policy')->default(true); // Meets password policy
            $table->json('policy_violations')->nullable(); // Policy violations (if any)
            
            // Change context
            $table->enum('change_reason', [
                'initial', 'user_requested', 'admin_forced', 'policy_expired', 
                'security_breach', 'forgot_password', 'first_login'
            ])->default('user_requested');
            $table->string('change_method', 30)->default('form'); // How password was changed
            $table->text('change_notes')->nullable(); // Additional notes about change
            
            // Security tracking
            $table->string('ip_address', 45)->nullable(); // IP where password was changed
            $table->text('user_agent')->nullable(); // User agent when changed
            $table->boolean('is_compromised')->default(false); // Password was compromised
            $table->timestamp('compromised_at')->nullable(); // When compromise was detected
            $table->text('compromise_reason')->nullable(); // Reason for compromise
            
            // Usage tracking
            $table->integer('login_count')->default(0); // How many times this password was used
            $table->timestamp('first_used_at')->nullable(); // First login with this password
            $table->timestamp('last_used_at')->nullable(); // Last login with this password
            $table->timestamp('retired_at')->nullable(); // When password was retired
            
            // Password validation
            $table->boolean('is_temporary')->default(false); // Temporary password flag
            $table->boolean('requires_change')->default(false); // Must be changed on next login
            $table->integer('days_until_expiry')->nullable(); // Days until expiration
            $table->json('validation_errors')->nullable(); // Validation errors (if any)
            
            // Audit fields
            $table->uuid('changed_by')->nullable(); // Who changed the password
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            
            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('idbi_users')->onDelete('cascade');
            $table->foreign('changed_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('idbi_users')->onDelete('set null');
            
            // Indexes for performance and security
            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'is_current']);
            $table->index(['password_hash']); // For checking password reuse
            $table->index(['is_current']);
            $table->index(['expires_at']);
            $table->index(['is_compromised']);
            $table->index(['change_reason']);
            $table->index(['is_temporary']);
            $table->index(['requires_change']);
            $table->index(['changed_by']);
            $table->index(['created_by']);
            $table->index(['updated_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_password_histories');
    }
};