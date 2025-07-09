<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the idbi_role_permissions pivot table to establish
     * many-to-many relationship between roles and permissions.
     */
    public function up(): void
    {
        Schema::create('idbi_role_permissions', function (Blueprint $table) {
            // Primary key as UUID
            $table->uuid('id')->primary();
            
            // Foreign keys
            $table->uuid('role_id');
            $table->uuid('permission_id');
            
            // Permission configuration for this role
            $table->boolean('granted')->default(true); // true = granted, false = explicitly denied
            $table->json('conditions')->nullable(); // Role-specific conditions for this permission
            $table->json('restrictions')->nullable(); // Additional restrictions for this role-permission
            
            // Audit fields
            $table->uuid('granted_by')->nullable(); // Who granted this permission
            $table->timestamp('granted_at')->nullable(); // When permission was granted
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign key constraints
            $table->foreign('role_id')->references('id')->on('idbi_roles')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('idbi_permissions')->onDelete('cascade');
            $table->foreign('granted_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('idbi_users')->onDelete('set null');
            
            // Unique constraint to prevent duplicate role-permission assignments
            $table->unique(['role_id', 'permission_id'], 'unique_role_permission');
            
            // Indexes for performance
            $table->index(['role_id', 'granted']);
            $table->index(['permission_id', 'granted']);
            $table->index(['granted_by']);
            $table->index(['granted_at']);
            $table->index(['created_by']);
            $table->index(['updated_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_role_permissions');
    }
};