<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for creating content_versions table
 * 
 * This migration creates the content_versions table to support:
 * - Content version history and tracking
 * - Content rollback and restoration
 * - Change tracking and audit trail
 * - Version comparison and diff functionality
 * 
 * @author Analytics Hub Team
 * @version 1.0
 * @since 2024-01-01
 */
class CreateContentVersionsTable extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the content_versions table to store historical versions
     * of content for rollback and audit purposes.
     * 
     * @return void
     */
    public function up(): void
    {
        Schema::create('content_versions', function (Blueprint $table) {
            // Primary key and timestamps
            $table->id();
            $table->timestamps();
            
            // Reference to the main content
            $table->unsignedBigInteger('content_id');
            
            // Version information
            $table->unsignedInteger('version_number');
            $table->string('description')->nullable(); // Description of changes
            
            // Snapshot of content data at the time of version creation
            $table->string('title');
            $table->string('slug');
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            
            // Content metadata snapshot
            $table->enum('type', ['custom', 'embedded']);
            $table->enum('status', ['draft', 'published', 'archived']);
            
            // Publishing information snapshot
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            // Content features snapshot
            $table->boolean('is_featured')->default(false);
            $table->boolean('allow_comments')->default(true);
            $table->boolean('is_searchable')->default(true);
            
            // SEO metadata snapshot
            $table->string('meta_title', 60)->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            
            // Media snapshot
            $table->string('featured_image')->nullable();
            
            // Access control snapshot
            $table->json('visibility_settings')->nullable();
            $table->json('access_permissions')->nullable();
            
            // Layout and custom fields snapshot
            $table->string('template')->nullable();
            $table->json('layout_settings')->nullable();
            $table->json('custom_fields')->nullable();
            
            // Version metadata
            $table->unsignedBigInteger('created_by'); // User who created this version
            $table->string('change_type')->default('update'); // create, update, restore, etc.
            $table->json('changes_summary')->nullable(); // Summary of what changed
            
            // Statistics at the time of version creation
            $table->unsignedBigInteger('view_count_snapshot')->default(0);
            $table->unsignedBigInteger('like_count_snapshot')->default(0);
            $table->unsignedBigInteger('share_count_snapshot')->default(0);
            $table->unsignedBigInteger('comment_count_snapshot')->default(0);
            $table->decimal('rating_snapshot', 3, 2)->default(0.00);
            
            // Indexes for performance
            $table->index(['content_id', 'version_number']);
            $table->index(['content_id', 'created_at']);
            $table->index(['created_by']);
            $table->index(['change_type']);
            $table->index(['created_at']);
            
            // Unique constraint to prevent duplicate version numbers for same content
            $table->unique(['content_id', 'version_number']);
            
            // Foreign key constraints
            $table->foreign('content_id')->references('id')->on('contents')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Drops the content_versions table and all associated indexes and constraints.
     * 
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('content_versions');
    }
}