<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the idbi_contents table for content management system (CMS)
     * functionality in the Analytics Hub.
     */
    public function up(): void
    {
        Schema::create('idbi_contents', function (Blueprint $table) {
            // Primary key as UUID
            $table->uuid('id')->primary();
            
            // Content identification
            $table->string('title', 255);
            $table->string('slug', 255)->unique(); // URL-friendly identifier
            $table->text('excerpt')->nullable(); // Short description
            $table->longText('content'); // Main content body
            
            // Content type and categorization
            $table->enum('type', ['page', 'post', 'announcement', 'help', 'faq', 'widget'])->default('page');
            $table->string('category', 100)->nullable(); // Content category
            $table->json('tags')->nullable(); // Content tags (array)
            
            // Content status and publishing
            $table->enum('status', ['draft', 'published', 'archived', 'scheduled'])->default('draft');
            $table->timestamp('published_at')->nullable(); // Publication date
            $table->timestamp('expires_at')->nullable(); // Expiration date (optional)
            $table->boolean('is_featured')->default(false); // Featured content flag
            
            // Content metadata
            $table->string('meta_title', 255)->nullable(); // SEO title
            $table->text('meta_description')->nullable(); // SEO description
            $table->json('meta_keywords')->nullable(); // SEO keywords (array)
            $table->string('featured_image', 500)->nullable(); // Featured image URL
            
            // Content configuration
            $table->boolean('allow_comments')->default(false); // Comments enabled
            $table->boolean('is_searchable')->default(true); // Include in search
            $table->json('visibility_settings')->nullable(); // Visibility rules
            $table->json('access_permissions')->nullable(); // Access control
            
            // Content statistics
            $table->integer('view_count')->default(0); // View counter
            $table->integer('like_count')->default(0); // Like counter
            $table->integer('comment_count')->default(0); // Comment counter
            $table->decimal('rating', 3, 2)->nullable(); // Average rating
            
            // Content relationships
            $table->uuid('parent_id')->nullable(); // Parent content (for hierarchical content)
            $table->json('related_content')->nullable(); // Related content IDs
            
            // Template and layout
            $table->string('template', 100)->nullable(); // Template to use for rendering
            $table->json('layout_settings')->nullable(); // Layout configuration
            $table->json('custom_fields')->nullable(); // Additional custom fields
            
            // Audit fields
            $table->uuid('author_id'); // Content author
            $table->uuid('editor_id')->nullable(); // Last editor
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign key constraints
            $table->foreign('parent_id')->references('id')->on('idbi_contents')->onDelete('cascade');
            $table->foreign('author_id')->references('id')->on('idbi_users')->onDelete('cascade');
            $table->foreign('editor_id')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('idbi_users')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['slug']);
            $table->index(['type', 'status']);
            $table->index(['status', 'published_at']);
            $table->index(['category', 'status']);
            $table->index(['is_featured', 'status']);
            $table->index(['author_id']);
            $table->index(['parent_id']);
            $table->index(['view_count']);
            $table->index(['created_at']);
            $table->index(['published_at']);
            $table->fullText(['title', 'content', 'excerpt']); // Full-text search
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_contents');
    }
};