<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the idbi_cache_locks table for cache locking mechanisms
     * in the Analytics Hub system.
     */
    public function up(): void
    {
        Schema::create('idbi_cache_locks', function (Blueprint $table) {
            // Primary key
            $table->string('key', 255)->primary(); // Lock key
            
            // Lock ownership
            $table->string('owner', 255); // Lock owner identifier
            $table->integer('expiration'); // Lock expiration timestamp
            
            // Lock metadata
            $table->string('type', 50)->default('cache'); // Lock type
            $table->string('resource', 255)->nullable(); // Resource being locked
            $table->text('description')->nullable(); // Lock description
            
            // Lock behavior
            $table->boolean('is_exclusive')->default(true); // Exclusive lock flag
            $table->integer('max_duration')->nullable(); // Maximum lock duration (seconds)
            $table->integer('timeout')->nullable(); // Lock timeout (seconds)
            $table->boolean('auto_release')->default(true); // Auto-release on expiration
            
            // Lock tracking
            $table->timestamp('acquired_at'); // When lock was acquired
            $table->timestamp('last_renewed_at')->nullable(); // Last renewal time
            $table->integer('renewal_count')->default(0); // Number of renewals
            $table->timestamp('released_at')->nullable(); // When lock was released
            
            // Lock context
            $table->uuid('user_id')->nullable(); // User who acquired lock
            $table->string('session_id', 255)->nullable(); // Session that owns lock
            $table->string('process_id', 100)->nullable(); // Process ID
            $table->string('server_id', 100)->nullable(); // Server identifier
            
            // Lock statistics
            $table->integer('wait_time_ms')->nullable(); // Time waited to acquire lock
            $table->integer('hold_time_ms')->nullable(); // Time lock was held
            $table->integer('contention_count')->default(0); // Number of contention events
            
            // Lock priority and queue
            $table->integer('priority')->default(5); // Lock priority (1=high, 5=low)
            $table->integer('queue_position')->nullable(); // Position in lock queue
            $table->json('queue_data')->nullable(); // Queue-related data
            
            // Lock validation
            $table->string('checksum', 255)->nullable(); // Lock validation checksum
            $table->boolean('is_valid')->default(true); // Lock validity flag
            $table->timestamp('validated_at')->nullable(); // Last validation time
            
            // Error handling
            $table->integer('error_count')->default(0); // Number of errors
            $table->text('last_error')->nullable(); // Last error message
            $table->timestamp('last_error_at')->nullable(); // Last error time
            
            // Audit fields
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('idbi_users')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['owner', 'expiration']);
            $table->index(['expiration']);
            $table->index(['type', 'expiration']);
            $table->index(['resource']);
            $table->index(['user_id']);
            $table->index(['session_id']);
            $table->index(['process_id']);
            $table->index(['priority', 'acquired_at']);
            $table->index(['is_valid']);
            $table->index(['acquired_at']);
            $table->index(['released_at']);
            $table->index(['created_by']);
            $table->index(['updated_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_cache_locks');
    }
};