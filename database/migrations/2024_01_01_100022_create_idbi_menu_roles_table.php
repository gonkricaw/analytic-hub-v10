<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the idbi_menu_roles pivot table for menu-role assignments
     * in the Analytics Hub system.
     */
    public function up(): void
    {
        Schema::create('idbi_menu_roles', function (Blueprint $table) {
            // Primary key
            $table->uuid('id')->primary();
            
            // Foreign keys
            $table->uuid('menu_id'); // Reference to idbi_menus
            $table->uuid('role_id'); // Reference to idbi_roles
            
            // Assignment configuration
            $table->boolean('is_granted')->default(true); // Access granted/denied
            $table->enum('access_type', ['view', 'manage', 'full'])->default('view'); // Access level
            $table->text('access_conditions')->nullable(); // Conditional access rules
            $table->json('restrictions')->nullable(); // Access restrictions
            
            // Menu visibility settings
            $table->boolean('is_visible')->default(true); // Menu visibility
            $table->boolean('show_in_navigation')->default(true); // Show in nav
            $table->boolean('show_children')->default(true); // Show child menus
            $table->integer('custom_order')->nullable(); // Custom menu order for role
            
            // Assignment metadata
            $table->text('assignment_reason')->nullable(); // Reason for assignment
            $table->json('assignment_data')->nullable(); // Additional assignment data
            $table->text('notes')->nullable(); // Assignment notes
            
            // Temporal assignment
            $table->timestamp('granted_at')->nullable(); // When access was granted
            $table->timestamp('expires_at')->nullable(); // Access expiration
            $table->boolean('is_temporary')->default(false); // Temporary assignment
            $table->integer('duration_hours')->nullable(); // Assignment duration
            
            // Assignment tracking
            $table->uuid('granted_by')->nullable(); // Who granted access
            $table->uuid('revoked_by')->nullable(); // Who revoked access
            $table->timestamp('revoked_at')->nullable(); // Revocation timestamp
            $table->text('revocation_reason')->nullable(); // Revocation reason
            
            // Override behavior
            $table->boolean('overrides_parent')->default(false); // Override parent menu access
            $table->uuid('overridden_menu_id')->nullable(); // Menu being overridden
            $table->text('override_justification')->nullable(); // Override reason
            
            // Usage tracking
            $table->integer('access_count')->default(0); // Number of accesses
            $table->timestamp('last_accessed_at')->nullable(); // Last access time
            $table->timestamp('first_accessed_at')->nullable(); // First access time
            $table->json('access_statistics')->nullable(); // Access statistics
            
            // Status and validation
            $table->boolean('is_active')->default(true); // Assignment active status
            $table->boolean('requires_approval')->default(false); // Needs approval
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->nullable();
            $table->uuid('approved_by')->nullable(); // Who approved
            $table->timestamp('approved_at')->nullable(); // Approval timestamp
            
            // Security and compliance
            $table->boolean('is_sensitive')->default(false); // Sensitive menu access
            $table->boolean('requires_justification')->default(false); // Needs justification
            $table->text('compliance_notes')->nullable(); // Compliance information
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical'])->default('low');
            
            // Audit fields
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            
            // Unique constraint
            $table->unique(['menu_id', 'role_id'], 'unique_menu_role');
            
            // Foreign key constraints
            $table->foreign('menu_id')->references('id')->on('idbi_menus')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('idbi_roles')->onDelete('cascade');
            $table->foreign('granted_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('revoked_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('overridden_menu_id')->references('id')->on('idbi_menus')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('idbi_users')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['menu_id', 'is_active']);
            $table->index(['role_id', 'is_active']);
            $table->index(['is_granted', 'is_active']);
            $table->index(['access_type']);
            $table->index(['is_visible']);
            $table->index(['show_in_navigation']);
            $table->index(['expires_at']);
            $table->index(['is_temporary']);
            $table->index(['granted_by']);
            $table->index(['revoked_by']);
            $table->index(['approval_status']);
            $table->index(['is_sensitive']);
            $table->index(['risk_level']);
            $table->index(['created_by']);
            $table->index(['updated_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_menu_roles');
    }
};