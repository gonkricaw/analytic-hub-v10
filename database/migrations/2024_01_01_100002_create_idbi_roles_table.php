<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the idbi_roles table for role-based access control (RBAC)
     * in the Analytics Hub system.
     */
    public function up(): void
    {
        Schema::create('idbi_roles', function (Blueprint $table) {
            // Primary key as UUID
            $table->uuid('id')->primary();
            
            // Role information
            $table->string('name', 100)->unique(); // e.g., 'Administrator', 'Stakeholder', 'Manager'
            $table->string('display_name', 150); // Human-readable name
            $table->text('description')->nullable();
            
            // Role hierarchy and permissions
            $table->integer('level')->default(1); // 1=highest (admin), higher numbers = lower access
            $table->boolean('is_system_role')->default(false); // Cannot be deleted if true
            $table->boolean('is_default')->default(false); // Default role for new users
            
            // Role status
            $table->enum('status', ['active', 'inactive'])->default('active');
            
            // Role configuration
            $table->json('permissions_cache')->nullable(); // Cached permissions for performance
            $table->json('settings')->nullable(); // Role-specific settings
            
            // Audit fields
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['name', 'status']);
            $table->index(['level', 'status']);
            $table->index(['is_system_role']);
            $table->index(['is_default']);
            $table->index(['created_by']);
            $table->index(['updated_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_roles');
    }
};