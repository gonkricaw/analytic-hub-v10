<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the idbi_blacklisted_ips table for IP address blacklisting
     * in the Analytics Hub system.
     */
    public function up(): void
    {
        Schema::create('idbi_blacklisted_ips', function (Blueprint $table) {
            // Primary key
            $table->uuid('id')->primary();
            
            // IP address information
            $table->string('ip_address', 45)->unique();
            $table->string('ip_range', 100)->nullable();
            $table->string('ip_version', 10)->default('ipv4');
            
            // Blacklist details
            $table->string('blacklist_type', 50)->default('manual');
            $table->text('reason');
            $table->text('description')->nullable();
            $table->string('severity', 20)->default('medium');
            
            // Blacklist status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_permanent')->default(false);
            $table->timestamp('blacklisted_at');
            $table->timestamp('expires_at')->nullable();
            
            // Activity tracking
            $table->integer('failed_login_count')->default(0);
            $table->integer('suspicious_activity_count')->default(0);
            $table->timestamp('last_seen_at')->nullable();
            
            // Blacklist management
            $table->uuid('blacklisted_by');
            $table->uuid('removed_by')->nullable();
            $table->timestamp('removed_at')->nullable();
            
            // Audit fields
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['is_active', 'expires_at']);
            $table->index(['blacklist_type']);
            $table->index(['severity']);
            $table->index(['blacklisted_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_blacklisted_ips');
    }
};