<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the idbi_email_templates table for managing email templates
     * in the Analytics Hub system.
     */
    public function up(): void
    {
        Schema::create('idbi_email_templates', function (Blueprint $table) {
            // Primary key as UUID
            $table->uuid('id')->primary();
            
            // Template identification
            $table->string('name', 100)->unique(); // Internal template name
            $table->string('display_name', 150); // Human-readable name
            $table->text('description')->nullable();
            
            // Email template content
            $table->string('subject', 255); // Email subject line
            $table->longText('body_html'); // HTML email body
            $table->longText('body_text')->nullable(); // Plain text email body
            
            // Template categorization
            $table->string('category', 50); // e.g., 'authentication', 'notification', 'report'
            $table->enum('type', ['system', 'user', 'automated'])->default('system');
            $table->string('event_trigger', 100)->nullable(); // Event that triggers this template
            
            // Template configuration
            $table->boolean('is_active')->default(true); // Template status
            $table->boolean('is_system_template')->default(false); // Cannot be deleted if true
            $table->json('variables')->nullable(); // Available template variables
            $table->json('default_data')->nullable(); // Default data for variables
            
            // Email settings
            $table->string('from_email', 255)->nullable(); // Override default from email
            $table->string('from_name', 100)->nullable(); // Override default from name
            $table->string('reply_to', 255)->nullable(); // Reply-to email
            $table->json('cc_emails')->nullable(); // CC email addresses (array)
            $table->json('bcc_emails')->nullable(); // BCC email addresses (array)
            
            // Template metadata
            $table->string('language', 10)->default('id'); // Template language (Indonesian)
            $table->integer('priority')->default(3); // Email priority (1=high, 3=normal, 5=low)
            $table->json('attachments')->nullable(); // Default attachments
            $table->json('headers')->nullable(); // Additional email headers
            
            // Template versioning
            $table->string('version', 20)->default('1.0'); // Template version
            $table->uuid('parent_template_id')->nullable(); // Parent template for versioning
            $table->boolean('is_current_version')->default(true); // Current active version
            
            // Usage statistics
            $table->integer('usage_count')->default(0); // How many times used
            $table->timestamp('last_used_at')->nullable(); // Last usage timestamp
            
            // Audit fields
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign key constraints
            $table->foreign('created_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('idbi_users')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['name', 'is_active']);
            $table->index(['category', 'type']);
            $table->index(['event_trigger']);
            $table->index(['is_system_template']);
            $table->index(['language', 'is_active']);
            $table->index(['parent_template_id']);
            $table->index(['is_current_version']);
            $table->index(['usage_count']);
            $table->index(['last_used_at']);
            $table->index(['created_by']);
            $table->index(['updated_by']);
        });
        
        // Add self-referencing foreign key after table creation
        Schema::table('idbi_email_templates', function (Blueprint $table) {
            $table->foreign('parent_template_id')->references('id')->on('idbi_email_templates')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_email_templates');
    }
};