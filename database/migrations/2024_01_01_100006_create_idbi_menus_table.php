<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the idbi_menus table for dynamic menu management
     * in the Analytics Hub system.
     */
    public function up(): void
    {
        Schema::create('idbi_menus', function (Blueprint $table) {
            // Primary key as UUID
            $table->uuid('id')->primary();
            
            // Menu identification
            $table->string('name', 100); // Internal name
            $table->string('title', 150); // Display title
            $table->text('description')->nullable();
            
            // Menu hierarchy
            $table->uuid('parent_id')->nullable(); // For nested menus
            $table->integer('sort_order')->default(0); // Menu ordering
            $table->integer('level')->default(1); // Menu depth level
            
            // Menu configuration
            $table->string('url', 255)->nullable(); // Menu URL/route
            $table->string('route_name', 100)->nullable(); // Laravel route name
            $table->string('icon', 50)->nullable(); // Icon class (e.g., FontAwesome)
            $table->string('target', 20)->default('_self'); // Link target (_self, _blank)
            
            // Menu type and behavior
            $table->enum('type', ['link', 'dropdown', 'separator', 'header'])->default('link');
            $table->boolean('is_external')->default(false); // External link flag
            $table->boolean('is_active')->default(true); // Menu visibility
            $table->boolean('is_system_menu')->default(false); // Cannot be deleted if true
            
            // Permission and access control
            $table->uuid('required_permission_id')->nullable(); // Required permission to view
            $table->json('required_roles')->nullable(); // Required roles (array of role IDs)
            $table->json('visibility_conditions')->nullable(); // Additional visibility conditions
            
            // Menu metadata
            $table->json('attributes')->nullable(); // Additional HTML attributes
            $table->json('metadata')->nullable(); // Additional metadata
            $table->string('css_class', 100)->nullable(); // Custom CSS classes
            
            // Audit fields
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign key constraints (except self-referencing)
            $table->foreign('required_permission_id')->references('id')->on('idbi_permissions')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('idbi_users')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['parent_id', 'sort_order']);
            $table->index(['is_active', 'sort_order']);
            $table->index(['type', 'is_active']);
            $table->index(['route_name']);
            $table->index(['required_permission_id']);
            $table->index(['is_system_menu']);
            $table->index(['created_by']);
            $table->index(['updated_by']);
        });
        
        // Add self-referencing foreign key constraint after table creation
        Schema::table('idbi_menus', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('idbi_menus')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_menus');
    }
};