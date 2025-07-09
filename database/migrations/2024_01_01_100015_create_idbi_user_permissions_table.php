<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the idbi_user_permissions pivot table for direct user-to-permission
     * assignments in the Analytics Hub RBAC system.
     */
    public function up(): void
    {
        Schema::create('idbi_user_permissions', function (Blueprint $table) {
            // Primary key as UUID
            $table->uuid('id')->primary();
            
            // Foreign keys
            $table->uuid('user_id');
            $table->uuid('permission_id');
            
            // Permission configuration
            $table->boolean('granted')->default(true); // true = granted, false = explicitly denied
            $table->enum('assignment_type', ['direct', 'inherited', 'temporary', 'override'])->default('direct');
            $table->text('assignment_reason')->nullable(); // Reason for direct assignment
            
            // Permission scope and conditions
            $table->json('conditions')->nullable(); // User-specific conditions for this permission
            $table->json('restrictions')->nullable(); // Additional restrictions
            $table->json('scope_data')->nullable(); // Scope limitations (e.g., specific resources)
            
            // Temporal permissions
            $table->timestamp('granted_at')->nullable(); // When permission was granted
            $table->timestamp('expires_at')->nullable(); // Permission expiration (optional)
            $table->boolean('is_temporary')->default(false); // Temporary permission flag
            $table->integer('duration_hours')->nullable(); // Duration in hours (for temporary permissions)
            
            // Assignment tracking
            $table->uuid('granted_by')->nullable(); // Who granted this permission
            $table->uuid('revoked_by')->nullable(); // Who revoked this permission
            $table->timestamp('revoked_at')->nullable(); // When permission was revoked
            $table->text('revocation_reason')->nullable(); // Reason for revocation
            
            // Override behavior
            $table->boolean('overrides_role')->default(false); // Overrides role-based permissions
            $table->uuid('overridden_role_id')->nullable(); // Which role permission this overrides
            $table->text('override_justification')->nullable(); // Justification for override
            
            // Usage tracking
            $table->integer('usage_count')->default(0); // How many times permission was used
            $table->timestamp('last_used_at')->nullable(); // Last time permission was used
            $table->timestamp('first_used_at')->nullable(); // First time permission was used
            
            // Status and validation
            $table->boolean('is_active')->default(true); // Permission is currently active
            $table->boolean('requires_approval')->default(false); // Requires approval to use
            $table->enum('approval_status', ['pending', 'approved', 'rejected', 'not_required'])->default('not_required');
            $table->uuid('approved_by')->nullable(); // Who approved the permission
            $table->timestamp('approved_at')->nullable(); // When permission was approved
            
            // Security and compliance
            $table->boolean('is_sensitive')->default(false); // Sensitive permission flag
            $table->boolean('requires_justification')->default(false); // Requires justification for use
            $table->json('compliance_notes')->nullable(); // Compliance-related notes
            $table->string('risk_level', 20)->default('low'); // low, medium, high, critical
            
            // Audit fields
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('idbi_users')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('idbi_permissions')->onDelete('cascade');
            $table->foreign('granted_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('revoked_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('overridden_role_id')->references('id')->on('idbi_roles')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('idbi_users')->onDelete('set null');
            
            // Unique constraint to prevent duplicate user-permission assignments
            $table->unique(['user_id', 'permission_id'], 'unique_user_permission');
            
            // Indexes for performance
            $table->index(['user_id', 'granted', 'is_active']);
            $table->index(['permission_id', 'granted']);
            $table->index(['assignment_type']);
            $table->index(['expires_at']);
            $table->index(['is_temporary']);
            $table->index(['granted_by']);
            $table->index(['approval_status']);
            $table->index(['is_sensitive']);
            $table->index(['risk_level']);
            $table->index(['last_used_at']);
            $table->index(['created_by']);
            $table->index(['updated_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_user_permissions');
    }
};