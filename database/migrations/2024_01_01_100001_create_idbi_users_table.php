<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the idbi_users table with UUID primary key and all required fields
     * for the Analytics Hub user management system.
     */
    public function up(): void
    {
        Schema::create('idbi_users', function (Blueprint $table) {
            // Primary key as UUID
            $table->uuid('id')->primary();
            
            // Basic user information
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 255)->unique();
            $table->string('username', 50)->unique()->nullable();
            
            // Authentication fields
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('is_first_login')->default(true);
            $table->timestamp('password_changed_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            
            // User status and preferences
            $table->enum('status', ['active', 'suspended', 'pending', 'deleted'])->default('pending');
            $table->boolean('terms_accepted')->default(false);
            $table->timestamp('terms_accepted_at')->nullable();
            $table->boolean('email_notifications')->default(true);
            
            // Profile information
            $table->text('bio')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('department', 100)->nullable();
            $table->string('position', 100)->nullable();
            
            // Security tracking
            $table->integer('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->string('remember_token')->nullable();
            
            // Audit fields
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['email', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['last_login_at']);
            $table->index(['created_by']);
            $table->index(['updated_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_users');
    }
};