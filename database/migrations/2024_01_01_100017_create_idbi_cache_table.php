<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the idbi_cache table for enhanced caching functionality
     * in the Analytics Hub system.
     */
    public function up(): void
    {
        Schema::create('idbi_cache', function (Blueprint $table) {
            // Primary key
            $table->string('key', 255)->primary(); // Cache key
            
            // Cache data
            $table->longText('value'); // Cached value
            $table->integer('expiration'); // Expiration timestamp
            
            // Cache metadata
            $table->string('store', 50)->default('default'); // Cache store name
            $table->string('tag', 100)->nullable(); // Cache tag for grouping
            $table->json('tags')->nullable(); // Multiple tags (array)
            $table->string('type', 50)->default('general'); // Cache type
            
            // Cache statistics
            $table->integer('hit_count')->default(0); // Number of cache hits
            $table->integer('miss_count')->default(0); // Number of cache misses
            $table->timestamp('last_accessed_at')->nullable(); // Last access time
            $table->timestamp('created_at'); // Cache creation time
            $table->timestamp('updated_at')->nullable(); // Last update time
            
            // Cache size and performance
            $table->integer('size_bytes')->nullable(); // Size in bytes
            $table->integer('compression_ratio')->nullable(); // Compression ratio (if compressed)
            $table->boolean('is_compressed')->default(false); // Compression flag
            $table->string('compression_method', 20)->nullable(); // Compression method
            
            // Cache behavior
            $table->boolean('is_persistent')->default(false); // Persistent cache flag
            $table->integer('ttl_seconds')->nullable(); // Time to live in seconds
            $table->boolean('auto_refresh')->default(false); // Auto-refresh flag
            $table->timestamp('refresh_at')->nullable(); // Next refresh time
            
            // Cache source and dependencies
            $table->string('source_type', 100)->nullable(); // Source type (model, query, etc.)
            $table->string('source_id', 255)->nullable(); // Source identifier
            $table->json('dependencies')->nullable(); // Cache dependencies
            $table->string('dependency_hash', 255)->nullable(); // Dependencies hash
            
            // Cache validation
            $table->string('checksum', 255)->nullable(); // Data checksum
            $table->string('version', 20)->default('1.0'); // Cache version
            $table->boolean('is_valid')->default(true); // Validity flag
            $table->timestamp('validated_at')->nullable(); // Last validation time
            
            // User and context
            $table->uuid('user_id')->nullable(); // Associated user (for user-specific cache)
            $table->string('context', 100)->nullable(); // Cache context
            $table->json('context_data')->nullable(); // Additional context data
            
            // Performance tracking
            $table->integer('generation_time_ms')->nullable(); // Time to generate cache (ms)
            $table->integer('serialization_time_ms')->nullable(); // Serialization time (ms)
            $table->string('serialization_method', 30)->default('php'); // Serialization method
            
            // Cache priority and cleanup
            $table->integer('priority')->default(5); // Cache priority (1=high, 5=low)
            $table->boolean('is_locked')->default(false); // Lock flag (for atomic operations)
            $table->timestamp('locked_at')->nullable(); // Lock timestamp
            $table->string('locked_by', 255)->nullable(); // Lock owner
            
            // Audit fields
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            
            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('idbi_users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('idbi_users')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['expiration']);
            $table->index(['store', 'expiration']);
            $table->index(['tag', 'expiration']);
            $table->index(['type', 'expiration']);
            $table->index(['user_id', 'expiration']);
            $table->index(['source_type', 'source_id']);
            $table->index(['is_persistent']);
            $table->index(['auto_refresh', 'refresh_at']);
            $table->index(['priority', 'expiration']);
            $table->index(['is_locked']);
            $table->index(['created_at']);
            $table->index(['last_accessed_at']);
            $table->index(['created_by']);
            $table->index(['updated_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_cache');
    }
};