<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the idbi_system_configs table for managing system configuration
     * settings in the Analytics Hub system.
     */
    public function up(): void
    {
        Schema::create('idbi_system_configs', function (Blueprint $table) {
            // Primary key as UUID
            $table->uuid('id')->primary();
            
            // Configuration identification
            $table->string('key', 100)->unique(); // Configuration key (e.g., 'app.name', 'mail.driver')
            $table->string('display_name', 150); // Human-readable name
            $table->text('description')->nullable(); // Configuration description
            
            // Configuration value
            $table->longText('value')->nullable(); // Configuration value (can be JSON)
            $table->longText('default_value')->nullable(); // Default value
            $table->enum('data_type', ['string', 'integer', 'float', 'boolean', 'json', 'array', 'text'])->default('string');
            
            // Configuration categorization
            $table->string('group', 50); // Configuration group (e.g., 'app', 'mail', 'database')
            $table->string('category', 50)->nullable(); // Sub-category
            $table->integer('sort_order')->default(0); // Display order
            
            // Configuration behavior
            $table->boolean('is_public')->default(false); // Can be accessed publicly
            $table->boolean('is_editable')->default(true); // Can be edited via UI
            $table->boolean('is_system_config')->default(false); // System configuration (cannot be deleted)
            $table->boolean('requires_restart')->default(false); // Requires app restart after change
            
            // Validation and constraints
            $table->json('validation_rules')->nullable(); // Validation rules for the value
            $table->json('options')->nullable(); // Available options (for select/radio inputs)
            $table->string('input_type', 30)->default('text'); // UI input type (text, select, checkbox, etc.)
            $table->text('help_text')->nullable(); // Help text for UI
            
            // Configuration status
            $table->boolean('is_active')->default(true); // Configuration is active
            $table->boolean('is_encrypted')->default(false); // Value is encrypted
            $table->timestamp('last_changed_at')->nullable(); // Last time value was changed
            
            // Environment and deployment
            $table->json('environments')->nullable(); // Environments where this config applies
            $table->string('deployment_stage', 20)->default('all'); // all, development, staging, production
            
            // Configuration metadata
            $table->json('metadata')->nullable(); // Additional metadata
            $table->string('source', 50)->default('database'); // Source of configuration (database, file, env)
            $table->integer('version')->default(1); // Configuration version
            
            // Change tracking
            $table->uuid('last_changed_by')->nullable(); // Who last changed this config
            $table->text('change_reason')->nullable(); // Reason for last change
            $table->json('change_history')->nullable(); // History of changes
            
            // Audit fields
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign key constraints
            $table->foreign('last_changed_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('idbi_users')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['key']);
            $table->index(['group', 'category']);
            $table->index(['is_public', 'is_active']);
            $table->index(['is_system_config']);
            $table->index(['group', 'sort_order']);
            $table->index(['deployment_stage']);
            $table->index(['last_changed_at']);
            $table->index(['last_changed_by']);
            $table->index(['created_by']);
            $table->index(['updated_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_system_configs');
    }
};