<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the idbi_user_roles pivot table to establish
     * many-to-many relationship between users and roles.
     */
    public function up(): void
    {
        Schema::create('idbi_user_roles', function (Blueprint $table) {
            // Primary key as UUID
            $table->uuid('id')->primary();
            
            // Foreign keys
            $table->uuid('user_id');
            $table->uuid('role_id');
            
            // Role assignment configuration
            $table->boolean('is_active')->default(true); // Role can be temporarily disabled
            $table->timestamp('assigned_at')->nullable(); // When role was assigned
            $table->timestamp('expires_at')->nullable(); // Role expiration (optional)
            $table->text('assignment_reason')->nullable(); // Reason for role assignment
            
            // Assignment tracking
            $table->uuid('assigned_by')->nullable(); // Who assigned this role
            $table->uuid('revoked_by')->nullable(); // Who revoked this role (if applicable)
            $table->timestamp('revoked_at')->nullable(); // When role was revoked
            $table->text('revocation_reason')->nullable(); // Reason for role revocation
            
            // Audit fields
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('idbi_users')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('idbi_roles')->onDelete('cascade');
            $table->foreign('assigned_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('revoked_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('idbi_users')->onDelete('set null');
            
            // Unique constraint to prevent duplicate user-role assignments
            $table->unique(['user_id', 'role_id'], 'unique_user_role');
            
            // Indexes for performance
            $table->index(['user_id', 'is_active']);
            $table->index(['role_id', 'is_active']);
            $table->index(['assigned_at']);
            $table->index(['expires_at']);
            $table->index(['assigned_by']);
            $table->index(['revoked_by']);
            $table->index(['created_by']);
            $table->index(['updated_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_user_roles');
    }
};