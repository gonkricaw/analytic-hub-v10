<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the idbi_email_queue table for email queue management
     * in the Analytics Hub system.
     */
    public function up(): void
    {
        Schema::create('idbi_email_queue', function (Blueprint $table) {
            // Primary key
            $table->uuid('id')->primary();
            
            // Email identification
            $table->string('message_id', 255)->unique()->nullable(); // Email message ID
            $table->uuid('template_id')->nullable(); // Reference to email template
            $table->string('subject', 500); // Email subject
            $table->string('queue_name', 100)->default('emails'); // Queue name
            
            // Recipient information
            $table->string('to_email', 255); // Primary recipient
            $table->string('to_name', 255)->nullable(); // Recipient name
            $table->json('cc_recipients')->nullable(); // CC recipients
            $table->json('bcc_recipients')->nullable(); // BCC recipients
            $table->json('reply_to')->nullable(); // Reply-to addresses
            
            // Sender information
            $table->string('from_email', 255); // Sender email
            $table->string('from_name', 255)->nullable(); // Sender name
            $table->string('sender_email', 255)->nullable(); // Actual sender
            $table->string('return_path', 255)->nullable(); // Return path
            
            // Email content
            $table->longText('html_body')->nullable(); // HTML content
            $table->longText('text_body')->nullable(); // Plain text content
            $table->json('template_data')->nullable(); // Template variables
            $table->json('attachments')->nullable(); // Attachment information
            
            // Email metadata
            $table->enum('email_type', [
                'transactional', 'notification', 'marketing', 'system', 'invitation'
            ])->default('transactional');
            $table->string('category', 100)->nullable(); // Email category
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->string('language', 10)->default('en'); // Email language
            
            // Scheduling
            $table->timestamp('scheduled_at')->nullable(); // Scheduled send time
            $table->timestamp('send_after')->nullable(); // Earliest send time
            $table->timestamp('expires_at')->nullable(); // Email expiration
            $table->boolean('is_immediate')->default(false); // Send immediately
            
            // Queue status
            $table->enum('status', [
                'pending', 'processing', 'sent', 'failed', 'cancelled', 'expired'
            ])->default('pending');
            $table->text('status_message')->nullable(); // Status description
            $table->json('status_data')->nullable(); // Additional status info
            
            // Delivery tracking
            $table->timestamp('sent_at')->nullable(); // Actual send time
            $table->timestamp('delivered_at')->nullable(); // Delivery confirmation
            $table->timestamp('opened_at')->nullable(); // First open time
            $table->timestamp('clicked_at')->nullable(); // First click time
            $table->integer('open_count')->default(0); // Number of opens
            $table->integer('click_count')->default(0); // Number of clicks
            
            // Retry mechanism
            $table->integer('attempts')->default(0); // Send attempts
            $table->integer('max_attempts')->default(3); // Maximum attempts
            $table->timestamp('next_retry_at')->nullable(); // Next retry time
            $table->integer('retry_delay')->default(300); // Retry delay (seconds)
            $table->json('retry_history')->nullable(); // Retry attempt history
            
            // Error handling
            $table->text('error_message')->nullable(); // Last error message
            $table->longText('error_details')->nullable(); // Detailed error info
            $table->string('error_code', 50)->nullable(); // Error code
            $table->timestamp('failed_at')->nullable(); // Failure timestamp
            $table->json('bounce_data')->nullable(); // Bounce information
            
            // Email tracking
            $table->string('tracking_id', 255)->nullable(); // Tracking identifier
            $table->json('tracking_data')->nullable(); // Tracking information
            $table->boolean('track_opens')->default(true); // Track email opens
            $table->boolean('track_clicks')->default(true); // Track link clicks
            $table->string('campaign_id', 255)->nullable(); // Campaign identifier
            
            // User context
            $table->uuid('user_id')->nullable(); // Target user
            $table->uuid('sender_user_id')->nullable(); // Sending user
            $table->string('session_id', 255)->nullable(); // Session ID
            $table->string('ip_address', 45)->nullable(); // IP address
            $table->json('user_context')->nullable(); // User context data
            
            // Email headers
            $table->json('custom_headers')->nullable(); // Custom email headers
            $table->string('message_stream', 100)->nullable(); // Message stream
            $table->json('tags')->nullable(); // Email tags
            $table->json('metadata')->nullable(); // Additional metadata
            
            // Batch processing
            $table->string('batch_id', 255)->nullable(); // Batch identifier
            $table->integer('batch_size')->nullable(); // Batch size
            $table->integer('batch_position')->nullable(); // Position in batch
            $table->json('batch_data')->nullable(); // Batch information
            
            // Performance metrics
            $table->integer('processing_time_ms')->nullable(); // Processing time
            $table->integer('send_time_ms')->nullable(); // Send time
            $table->integer('queue_wait_time_ms')->nullable(); // Queue wait time
            $table->json('performance_data')->nullable(); // Performance metrics
            
            // Compliance and security
            $table->boolean('is_encrypted')->default(false); // Content encrypted
            $table->string('encryption_method', 50)->nullable(); // Encryption method
            $table->boolean('requires_consent')->default(false); // Requires user consent
            $table->boolean('consent_given')->default(false); // Consent status
            $table->json('compliance_data')->nullable(); // Compliance information
            
            // Notification settings
            $table->boolean('notify_on_delivery')->default(false); // Notify on delivery
            $table->boolean('notify_on_failure')->default(true); // Notify on failure
            $table->json('notification_recipients')->nullable(); // Who to notify
            
            // Audit fields
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('template_id')->references('id')->on('idbi_email_templates')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('sender_user_id')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('idbi_users')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['status', 'scheduled_at']);
            $table->index(['to_email', 'status']);
            $table->index(['email_type', 'status']);
            $table->index(['priority', 'scheduled_at']);
            $table->index(['user_id', 'status']);
            $table->index(['template_id']);
            $table->index(['batch_id']);
            $table->index(['campaign_id']);
            $table->index(['tracking_id']);
            $table->index(['queue_name', 'status']);
            $table->index(['next_retry_at']);
            $table->index(['expires_at']);
            $table->index(['sent_at']);
            $table->index(['failed_at']);
            $table->index(['created_by']);
            $table->index(['updated_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_email_queue');
    }
};