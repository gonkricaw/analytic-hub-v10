<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for creating contents table
 * 
 * This migration creates the contents table to support:
 * - Custom HTML content with rich text editing
 * - Embedded reports with AES-256 URL encryption
 * - SEO optimization with meta tags
 * - Access control and permissions
 * - Content versioning and statistics
 * - Featured images and media support
 * 
 * @author Analytics Hub Team
 * @version 1.0
 * @since 2024-01-01
 */
class CreateContentsTable extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the contents table with all necessary fields for content management,
     * including support for custom HTML content and encrypted embedded reports.
     * 
     * @return void
     */
    public function up(): void
    {
        Schema::create('contents', function (Blueprint $table) {
            // Primary key and timestamps
            $table->id();
            $table->timestamps();
            
            // Basic content information
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable(); // For custom HTML content
            
            // Content type and classification
            $table->enum('type', ['custom', 'embedded'])->default('custom');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            
            // Publishing and scheduling
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            // Content features
            $table->boolean('is_featured')->default(false);
            $table->boolean('allow_comments')->default(true);
            $table->boolean('is_searchable')->default(true);
            
            // SEO metadata
            $table->string('meta_title', 60)->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            
            // Media and assets
            $table->string('featured_image')->nullable();
            
            // Access control and permissions
            $table->json('visibility_settings')->nullable(); // {"visibility": "public|private|restricted"}
            $table->json('access_permissions')->nullable(); // {"allowed_roles": ["admin", "manager"], "allowed_users": []}
            
            // Content statistics and engagement
            $table->unsignedBigInteger('view_count')->default(0);
            $table->unsignedBigInteger('like_count')->default(0);
            $table->unsignedBigInteger('share_count')->default(0);
            $table->unsignedBigInteger('comment_count')->default(0);
            $table->decimal('rating', 3, 2)->default(0.00); // Average rating (0.00 to 5.00)
            
            // Content hierarchy and relationships
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->json('related_content')->nullable(); // Array of related content IDs
            
            // Layout and presentation
            $table->string('template')->nullable(); // Template name for custom layouts
            $table->json('layout_settings')->nullable(); // Custom layout configuration
            
            // Custom fields for extensibility
            $table->json('custom_fields')->nullable(); // For embedded URLs, encryption data, etc.
            
            // Author and ownership
            $table->unsignedBigInteger('user_id');
            
            // Indexes for performance
            $table->index(['status', 'published_at']);
            $table->index(['type', 'status']);
            $table->index(['is_featured', 'status']);
            $table->index(['user_id']);
            $table->index(['parent_id']);
            $table->index(['slug']);
            $table->index(['created_at']);
            $table->index(['view_count']);
            $table->fullText(['title', 'excerpt', 'content']); // Full-text search
            
            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('contents')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Drops the contents table and all associated indexes and constraints.
     * 
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
}