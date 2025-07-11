<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the idbi_user_avatars table for user avatar management
     * in the Analytics Hub system.
     */
    public function up(): void
    {
        Schema::create('idbi_user_avatars', function (Blueprint $table) {
            // Primary key
            $table->uuid('id')->primary();
            
            // User association
            $table->uuid('user_id'); // Associated user
            
            // Avatar file information
            $table->string('filename', 255); // Original filename
            $table->string('stored_filename', 255); // Stored filename
            $table->string('file_path', 500); // File storage path
            $table->string('file_url', 500)->nullable(); // Public URL
            $table->string('mime_type', 100); // MIME type
            $table->string('file_extension', 10); // File extension
            $table->bigInteger('file_size'); // File size in bytes
            $table->string('file_hash', 64)->nullable(); // File hash (SHA-256)
            
            // Image properties
            $table->integer('width')->nullable(); // Image width
            $table->integer('height')->nullable(); // Image height
            $table->decimal('aspect_ratio', 5, 3)->nullable(); // Aspect ratio
            $table->integer('color_depth')->nullable(); // Color depth
            $table->string('color_space', 50)->nullable(); // Color space
            $table->json('exif_data')->nullable(); // EXIF metadata
            $table->string('dominant_color', 7)->nullable(); // Dominant color (hex)
            $table->json('color_palette')->nullable(); // Color palette
            
            // Avatar variants/sizes
            $table->json('variants')->nullable(); // Different sizes/formats
            $table->string('thumbnail_path', 500)->nullable(); // Thumbnail path
            $table->string('small_path', 500)->nullable(); // Small size path
            $table->string('medium_path', 500)->nullable(); // Medium size path
            $table->string('large_path', 500)->nullable(); // Large size path
            $table->json('variant_urls')->nullable(); // URLs for variants
            
            // Avatar status
            $table->boolean('is_active')->default(true); // Active status
            $table->boolean('is_default')->default(false); // Default avatar
            $table->boolean('is_approved')->default(true); // Approval status
            $table->boolean('is_public')->default(false); // Public visibility
            $table->enum('status', ['pending', 'approved', 'rejected', 'processing'])->default('approved');
            
            // Upload information
            $table->enum('upload_source', ['local', 'url', 'social', 'gravatar', 'generated'])->default('local');
            $table->string('source_url', 500)->nullable(); // Source URL if uploaded from URL
            $table->string('social_provider', 50)->nullable(); // Social media provider
            $table->string('social_id', 255)->nullable(); // Social media ID
            $table->json('upload_metadata')->nullable(); // Upload metadata
            
            // Processing information
            $table->boolean('is_processed')->default(false); // Processing status
            $table->timestamp('processed_at')->nullable(); // Processing timestamp
            $table->json('processing_log')->nullable(); // Processing log
            $table->text('processing_error')->nullable(); // Processing errors
            $table->integer('processing_attempts')->default(0); // Processing attempts
            
            // Quality and validation
            $table->integer('quality_score')->nullable(); // Image quality score (0-100)
            $table->boolean('is_appropriate')->default(true); // Content appropriateness
            $table->json('moderation_results')->nullable(); // Content moderation results
            $table->boolean('requires_review')->default(false); // Manual review required
            $table->uuid('reviewed_by')->nullable(); // Who reviewed
            $table->timestamp('reviewed_at')->nullable(); // Review timestamp
            $table->text('review_notes')->nullable(); // Review notes
            
            // Usage tracking
            $table->integer('view_count')->default(0); // View count
            $table->timestamp('first_used_at')->nullable(); // First usage
            $table->timestamp('last_used_at')->nullable(); // Last usage
            $table->integer('download_count')->default(0); // Download count
            $table->json('usage_statistics')->nullable(); // Usage statistics
            
            // Storage information
            $table->string('storage_driver', 50)->default('local'); // Storage driver
            $table->string('storage_disk', 50)->default('public'); // Storage disk
            $table->string('storage_bucket', 255)->nullable(); // Cloud storage bucket
            $table->string('storage_region', 100)->nullable(); // Storage region
            $table->json('storage_metadata')->nullable(); // Storage metadata
            
            // CDN and caching
            $table->string('cdn_url', 500)->nullable(); // CDN URL
            $table->boolean('is_cached')->default(false); // Cache status
            $table->timestamp('cache_expires_at')->nullable(); // Cache expiration
            $table->string('cache_key', 255)->nullable(); // Cache key
            $table->json('cdn_metadata')->nullable(); // CDN metadata
            
            // Security and privacy
            $table->boolean('is_encrypted')->default(false); // Encryption status
            $table->string('encryption_method', 50)->nullable(); // Encryption method
            $table->boolean('requires_authentication')->default(false); // Auth required
            $table->json('access_permissions')->nullable(); // Access permissions
            $table->string('access_token', 255)->nullable(); // Access token
            $table->timestamp('token_expires_at')->nullable(); // Token expiration
            
            // Backup and versioning
            $table->boolean('is_backed_up')->default(false); // Backup status
            $table->timestamp('backed_up_at')->nullable(); // Backup timestamp
            $table->string('backup_location', 500)->nullable(); // Backup location
            $table->integer('version')->default(1); // Version number
            $table->uuid('previous_version_id')->nullable(); // Previous version
            $table->json('version_history')->nullable(); // Version history
            
            // Optimization
            $table->boolean('is_optimized')->default(false); // Optimization status
            $table->integer('original_size')->nullable(); // Original file size
            $table->integer('optimized_size')->nullable(); // Optimized file size
            $table->decimal('compression_ratio', 5, 3)->nullable(); // Compression ratio
            $table->json('optimization_settings')->nullable(); // Optimization settings
            
            // Analytics and insights
            $table->json('analytics_data')->nullable(); // Analytics data
            $table->integer('engagement_score')->nullable(); // Engagement score
            $table->json('performance_metrics')->nullable(); // Performance metrics
            $table->timestamp('last_analyzed_at')->nullable(); // Last analysis
            
            // Compliance and legal
            $table->boolean('has_consent')->default(true); // User consent
            $table->timestamp('consent_given_at')->nullable(); // Consent timestamp
            $table->json('compliance_data')->nullable(); // Compliance information
            $table->boolean('gdpr_compliant')->default(true); // GDPR compliance
            $table->text('legal_notes')->nullable(); // Legal notes
            
            // Expiration and cleanup
            $table->timestamp('expires_at')->nullable(); // Expiration timestamp
            $table->boolean('auto_delete')->default(false); // Auto-delete flag
            $table->timestamp('delete_after')->nullable(); // Auto-delete timestamp
            $table->boolean('is_archived')->default(false); // Archive status
            $table->timestamp('archived_at')->nullable(); // Archive timestamp
            
            // Tags and categorization
            $table->json('tags')->nullable(); // Tags
            $table->string('category', 100)->nullable(); // Category
            $table->text('description')->nullable(); // Description
            $table->json('metadata')->nullable(); // Additional metadata
            
            // Audit fields
            $table->uuid('uploaded_by')->nullable(); // Who uploaded
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            
            // Foreign key constraints (except self-referencing)
            $table->foreign('user_id')->references('id')->on('idbi_users')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('uploaded_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('idbi_users')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['user_id', 'is_active']);
            $table->index(['is_default']);
            $table->index(['is_approved', 'status']);
            $table->index(['upload_source']);
            $table->index(['is_processed']);
            $table->index(['mime_type']);
            $table->index(['file_size']);
            $table->index(['quality_score']);
            $table->index(['requires_review']);
            $table->index(['storage_driver']);
            $table->index(['is_cached']);
            $table->index(['is_backed_up']);
            $table->index(['is_optimized']);
            $table->index(['expires_at']);
            $table->index(['is_archived']);
            $table->index(['created_by']);
            $table->index(['updated_by']);
        });
        
        // Add self-referencing foreign key after table creation
        Schema::table('idbi_user_avatars', function (Blueprint $table) {
            $table->foreign('previous_version_id')->references('id')->on('idbi_user_avatars')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_user_avatars');
    }
};