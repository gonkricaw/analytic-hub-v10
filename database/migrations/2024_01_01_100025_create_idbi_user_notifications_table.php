<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the idbi_user_notifications pivot table for user-notification assignments
     * in the Analytics Hub system.
     */
    public function up(): void
    {
        Schema::create('idbi_user_notifications', function (Blueprint $table) {
            // Primary key
            $table->uuid('id')->primary();
            
            // Foreign keys
            $table->uuid('user_id'); // Reference to idbi_users
            $table->uuid('notification_id'); // Reference to idbi_notifications
            
            // Notification status
            $table->boolean('is_read')->default(false); // Read status
            $table->timestamp('read_at')->nullable(); // Read timestamp
            $table->boolean('is_dismissed')->default(false); // Dismissed status
            $table->timestamp('dismissed_at')->nullable(); // Dismissal timestamp
            $table->boolean('is_archived')->default(false); // Archived status
            $table->timestamp('archived_at')->nullable(); // Archive timestamp
            
            // Delivery tracking
            $table->enum('delivery_status', [
                'pending', 'delivered', 'failed', 'bounced', 'cancelled'
            ])->default('pending');
            $table->timestamp('delivered_at')->nullable(); // Delivery timestamp
            $table->text('delivery_message')->nullable(); // Delivery status message
            $table->json('delivery_data')->nullable(); // Delivery details
            
            // Notification preferences
            $table->boolean('email_sent')->default(false); // Email notification sent
            $table->boolean('sms_sent')->default(false); // SMS notification sent
            $table->boolean('push_sent')->default(false); // Push notification sent
            $table->boolean('in_app_shown')->default(false); // In-app notification shown
            
            // Delivery timestamps
            $table->timestamp('email_sent_at')->nullable(); // Email send time
            $table->timestamp('sms_sent_at')->nullable(); // SMS send time
            $table->timestamp('push_sent_at')->nullable(); // Push send time
            $table->timestamp('in_app_shown_at')->nullable(); // In-app show time
            
            // Interaction tracking
            $table->integer('view_count')->default(0); // Number of views
            $table->timestamp('first_viewed_at')->nullable(); // First view time
            $table->timestamp('last_viewed_at')->nullable(); // Last view time
            $table->boolean('action_taken')->default(false); // Action performed
            $table->timestamp('action_taken_at')->nullable(); // Action timestamp
            $table->text('action_details')->nullable(); // Action description
            
            // User response
            $table->enum('user_response', [
                'none', 'acknowledged', 'accepted', 'rejected', 'deferred'
            ])->default('none');
            $table->timestamp('response_at')->nullable(); // Response timestamp
            $table->text('response_notes')->nullable(); // User response notes
            $table->json('response_data')->nullable(); // Response details
            
            // Priority and urgency
            $table->enum('user_priority', ['low', 'normal', 'high', 'urgent'])->nullable();
            $table->boolean('is_pinned')->default(false); // Pinned by user
            $table->timestamp('pinned_at')->nullable(); // Pin timestamp
            $table->boolean('is_starred')->default(false); // Starred by user
            $table->timestamp('starred_at')->nullable(); // Star timestamp
            
            // Notification scheduling
            $table->timestamp('scheduled_for')->nullable(); // User-specific schedule
            $table->boolean('is_snoozed')->default(false); // Snoozed status
            $table->timestamp('snoozed_until')->nullable(); // Snooze end time
            $table->integer('snooze_count')->default(0); // Number of snoozes
            
            // Retry mechanism
            $table->integer('retry_count')->default(0); // Delivery retry count
            $table->timestamp('next_retry_at')->nullable(); // Next retry time
            $table->integer('max_retries')->default(3); // Maximum retries
            $table->json('retry_history')->nullable(); // Retry attempt history
            
            // Error handling
            $table->text('error_message')->nullable(); // Last error message
            $table->json('error_details')->nullable(); // Error details
            $table->timestamp('failed_at')->nullable(); // Failure timestamp
            $table->integer('failure_count')->default(0); // Number of failures
            
            // Device and context
            $table->string('device_type', 50)->nullable(); // Device type
            $table->string('device_id', 255)->nullable(); // Device identifier
            $table->string('platform', 50)->nullable(); // Platform (web, mobile, etc.)
            $table->string('app_version', 50)->nullable(); // App version
            $table->json('device_info')->nullable(); // Device information
            
            // Location and context
            $table->string('ip_address', 45)->nullable(); // IP address
            $table->string('user_agent', 500)->nullable(); // User agent
            $table->string('timezone', 50)->nullable(); // User timezone
            $table->json('location_data')->nullable(); // Location information
            
            // Personalization
            $table->json('personalization_data')->nullable(); // Personalized content
            $table->string('language', 10)->nullable(); // Notification language
            $table->json('custom_data')->nullable(); // Custom user data
            $table->text('user_notes')->nullable(); // User's personal notes
            
            // Analytics and tracking
            $table->string('tracking_id', 255)->nullable(); // Tracking identifier
            $table->json('analytics_data')->nullable(); // Analytics information
            $table->boolean('track_engagement')->default(true); // Track engagement
            $table->json('engagement_metrics')->nullable(); // Engagement data
            
            // Compliance and privacy
            $table->boolean('consent_given')->default(true); // User consent
            $table->timestamp('consent_at')->nullable(); // Consent timestamp
            $table->boolean('can_be_deleted')->default(true); // Deletion allowed
            $table->timestamp('delete_after')->nullable(); // Auto-delete time
            
            // Notification grouping
            $table->string('group_id', 255)->nullable(); // Notification group
            $table->integer('group_position')->nullable(); // Position in group
            $table->boolean('is_group_summary')->default(false); // Group summary
            $table->json('group_data')->nullable(); // Group information
            
            // Audit fields
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            
            // Unique constraint
            $table->unique(['user_id', 'notification_id'], 'unique_user_notification');
            
            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('idbi_users')->onDelete('cascade');
            $table->foreign('notification_id')->references('id')->on('idbi_notifications')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('idbi_users')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['user_id', 'is_read']);
            $table->index(['user_id', 'delivery_status']);
            $table->index(['notification_id', 'delivery_status']);
            $table->index(['is_read', 'created_at']);
            $table->index(['is_dismissed']);
            $table->index(['is_archived']);
            $table->index(['delivery_status']);
            $table->index(['user_response']);
            $table->index(['is_pinned']);
            $table->index(['is_starred']);
            $table->index(['is_snoozed', 'snoozed_until']);
            $table->index(['scheduled_for']);
            $table->index(['next_retry_at']);
            $table->index(['group_id']);
            $table->index(['tracking_id']);
            $table->index(['created_by']);
            $table->index(['updated_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_user_notifications');
    }
};