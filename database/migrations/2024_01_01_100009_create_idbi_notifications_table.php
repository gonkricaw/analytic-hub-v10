<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the idbi_notifications table for managing system notifications
     * in the Analytics Hub system.
     */
    public function up(): void
    {
        Schema::create('idbi_notifications', function (Blueprint $table) {
            // Primary key as UUID
            $table->uuid('id')->primary();
            
            // Notification targeting
            $table->uuid('user_id'); // Target user
            $table->string('notifiable_type', 100); // Polymorphic type (e.g., 'App\Models\User')
            $table->uuid('notifiable_id'); // Polymorphic ID
            
            // Notification content
            $table->string('title', 255); // Notification title
            $table->text('message'); // Notification message
            $table->string('type', 50); // e.g., 'info', 'success', 'warning', 'error', 'system'
            $table->string('category', 50)->nullable(); // e.g., 'security', 'report', 'system', 'user'
            
            // Notification data
            $table->json('data')->nullable(); // Additional notification data
            $table->json('action_data')->nullable(); // Data for notification actions
            $table->string('action_url', 500)->nullable(); // URL for notification action
            $table->string('action_text', 100)->nullable(); // Text for action button
            
            // Notification status
            $table->timestamp('read_at')->nullable(); // When notification was read
            $table->boolean('is_read')->default(false); // Read status flag
            $table->boolean('is_important')->default(false); // Important notification flag
            $table->boolean('is_dismissible')->default(true); // Can be dismissed
            
            // Notification delivery
            $table->enum('delivery_method', ['database', 'email', 'sms', 'push', 'all'])->default('database');
            $table->boolean('email_sent')->default(false); // Email delivery status
            $table->boolean('sms_sent')->default(false); // SMS delivery status
            $table->boolean('push_sent')->default(false); // Push notification status
            $table->timestamp('delivered_at')->nullable(); // Delivery timestamp
            
            // Notification scheduling
            $table->timestamp('scheduled_at')->nullable(); // Scheduled delivery time
            $table->timestamp('expires_at')->nullable(); // Notification expiration
            $table->integer('retry_count')->default(0); // Delivery retry count
            $table->timestamp('last_retry_at')->nullable(); // Last retry timestamp
            
            // Notification source
            $table->uuid('sender_id')->nullable(); // Who sent the notification
            $table->string('source_type', 100)->nullable(); // Source type (e.g., 'system', 'user', 'automated')
            $table->string('source_reference', 255)->nullable(); // Reference to source object
            
            // Notification metadata
            $table->string('icon', 50)->nullable(); // Notification icon
            $table->string('color', 20)->nullable(); // Notification color
            $table->json('metadata')->nullable(); // Additional metadata
            $table->integer('priority')->default(3); // Priority (1=high, 3=normal, 5=low)
            
            // Audit fields
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('idbi_users')->onDelete('cascade');
            $table->foreign('sender_id')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('idbi_users')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['user_id', 'is_read']);
            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index(['type', 'category']);
            $table->index(['is_read', 'created_at']);
            $table->index(['is_important', 'created_at']);
            $table->index(['scheduled_at']);
            $table->index(['expires_at']);
            $table->index(['delivery_method']);
            $table->index(['sender_id']);
            $table->index(['priority', 'created_at']);
            $table->index(['created_by']);
            $table->index(['updated_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_notifications');
    }
};