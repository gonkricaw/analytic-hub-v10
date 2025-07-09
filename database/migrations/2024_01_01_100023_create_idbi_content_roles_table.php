<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the idbi_content_roles pivot table for content-role assignments
     * in the Analytics Hub system.
     */
    public function up(): void
    {
        Schema::create('idbi_content_roles', function (Blueprint $table) {
            // Primary key
            $table->uuid('id')->primary();
            
            // Foreign keys
            $table->uuid('content_id'); // Reference to idbi_contents
            $table->uuid('role_id'); // Reference to idbi_roles
            
            // Access configuration
            $table->boolean('is_granted')->default(true); // Access granted/denied
            $table->enum('access_type', ['view', 'edit', 'manage', 'full'])->default('view'); // Access level
            $table->text('access_conditions')->nullable(); // Conditional access rules
            $table->json('restrictions')->nullable(); // Access restrictions
            
            // Content permissions
            $table->boolean('can_view')->default(true); // Can view content
            $table->boolean('can_edit')->default(false); // Can edit content
            $table->boolean('can_delete')->default(false); // Can delete content
            $table->boolean('can_publish')->default(false); // Can publish content
            $table->boolean('can_comment')->default(false); // Can comment on content
            $table->boolean('can_share')->default(false); // Can share content
            
            // Content visibility settings
            $table->boolean('is_visible')->default(true); // Content visibility
            $table->boolean('show_in_listings')->default(true); // Show in content lists
            $table->boolean('show_metadata')->default(true); // Show content metadata
            $table->boolean('allow_download')->default(false); // Allow content download
            
            // Assignment metadata
            $table->text('assignment_reason')->nullable(); // Reason for assignment
            $table->json('assignment_data')->nullable(); // Additional assignment data
            $table->text('notes')->nullable(); // Assignment notes
            $table->enum('priority', ['low', 'normal', 'high', 'critical'])->default('normal');
            
            // Temporal assignment
            $table->timestamp('granted_at')->nullable(); // When access was granted
            $table->timestamp('expires_at')->nullable(); // Access expiration
            $table->boolean('is_temporary')->default(false); // Temporary assignment
            $table->integer('duration_hours')->nullable(); // Assignment duration
            
            // Assignment tracking
            $table->uuid('granted_by')->nullable(); // Who granted access
            $table->uuid('revoked_by')->nullable(); // Who revoked access
            $table->timestamp('revoked_at')->nullable(); // Revocation timestamp
            $table->text('revocation_reason')->nullable(); // Revocation reason
            
            // Override behavior
            $table->boolean('overrides_default')->default(false); // Override default content access
            $table->uuid('overridden_content_id')->nullable(); // Content being overridden
            $table->text('override_justification')->nullable(); // Override reason
            
            // Usage tracking
            $table->integer('view_count')->default(0); // Number of views
            $table->integer('edit_count')->default(0); // Number of edits
            $table->timestamp('last_viewed_at')->nullable(); // Last view time
            $table->timestamp('last_edited_at')->nullable(); // Last edit time
            $table->timestamp('first_accessed_at')->nullable(); // First access time
            $table->json('access_statistics')->nullable(); // Access statistics
            
            // Content interaction
            $table->integer('comment_count')->default(0); // Comments made
            $table->integer('share_count')->default(0); // Times shared
            $table->integer('download_count')->default(0); // Downloads performed
            $table->json('interaction_data')->nullable(); // Interaction details
            
            // Status and validation
            $table->boolean('is_active')->default(true); // Assignment active status
            $table->boolean('requires_approval')->default(false); // Needs approval
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->nullable();
            $table->uuid('approved_by')->nullable(); // Who approved
            $table->timestamp('approved_at')->nullable(); // Approval timestamp
            
            // Security and compliance
            $table->boolean('is_sensitive')->default(false); // Sensitive content access
            $table->boolean('requires_justification')->default(false); // Needs justification
            $table->text('compliance_notes')->nullable(); // Compliance information
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->boolean('audit_access')->default(false); // Audit all access
            
            // Content workflow
            $table->enum('workflow_status', ['draft', 'review', 'approved', 'published'])->nullable();
            $table->uuid('reviewer_id')->nullable(); // Content reviewer
            $table->timestamp('reviewed_at')->nullable(); // Review timestamp
            $table->text('review_notes')->nullable(); // Review comments
            
            // Notification settings
            $table->boolean('notify_on_update')->default(false); // Notify on content update
            $table->boolean('notify_on_comment')->default(false); // Notify on new comment
            $table->boolean('notify_on_share')->default(false); // Notify on content share
            
            // Audit fields
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            
            // Unique constraint
            $table->unique(['content_id', 'role_id'], 'unique_content_role');
            
            // Foreign key constraints
            $table->foreign('content_id')->references('id')->on('idbi_contents')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('idbi_roles')->onDelete('cascade');
            $table->foreign('granted_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('revoked_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('overridden_content_id')->references('id')->on('idbi_contents')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('reviewer_id')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('idbi_users')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['content_id', 'is_active']);
            $table->index(['role_id', 'is_active']);
            $table->index(['is_granted', 'is_active']);
            $table->index(['access_type']);
            $table->index(['can_view', 'is_visible']);
            $table->index(['can_edit']);
            $table->index(['can_publish']);
            $table->index(['expires_at']);
            $table->index(['is_temporary']);
            $table->index(['granted_by']);
            $table->index(['revoked_by']);
            $table->index(['approval_status']);
            $table->index(['workflow_status']);
            $table->index(['is_sensitive']);
            $table->index(['risk_level']);
            $table->index(['audit_access']);
            $table->index(['created_by']);
            $table->index(['updated_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_content_roles');
    }
};