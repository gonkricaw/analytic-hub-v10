<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the idbi_permissions table for granular permission management
     * in the Analytics Hub RBAC system.
     */
    public function up(): void
    {
        Schema::create('idbi_permissions', function (Blueprint $table) {
            // Primary key as UUID
            $table->uuid('id')->primary();
            
            // Permission identification
            $table->string('name', 100)->unique(); // e.g., 'users.create', 'reports.view'
            $table->string('display_name', 150); // Human-readable name
            $table->text('description')->nullable();
            
            // Permission categorization
            $table->string('module', 50); // e.g., 'users', 'reports', 'dashboard'
            $table->string('action', 50); // e.g., 'create', 'read', 'update', 'delete'
            $table->string('resource', 100)->nullable(); // Specific resource if applicable
            
            // Permission hierarchy and grouping
            $table->uuid('parent_id')->nullable(); // For hierarchical permissions
            $table->string('group', 50)->nullable(); // Permission group for UI organization
            $table->integer('sort_order')->default(0); // For ordering in UI
            
            // Permission configuration
            $table->boolean('is_system_permission')->default(false); // Cannot be deleted if true
            $table->json('conditions')->nullable(); // Additional conditions for permission
            $table->json('metadata')->nullable(); // Additional metadata
            
            // Permission status
            $table->enum('status', ['active', 'inactive'])->default('active');
            
            // Audit fields
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['name', 'status']);
            $table->index(['module', 'action']);
            $table->index(['parent_id']);
            $table->index(['group', 'sort_order']);
            $table->index(['is_system_permission']);
            $table->index(['created_by']);
            $table->index(['updated_by']);
        });
        
        // Add self-referencing foreign key constraint after table creation
        Schema::table('idbi_permissions', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('idbi_permissions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_permissions');
    }
};