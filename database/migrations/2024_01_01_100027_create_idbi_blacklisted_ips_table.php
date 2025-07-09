<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the idbi_blacklisted_ips table for IP address blacklisting
     * in the Analytics Hub system.
     */
    public function up(): void
    {
        Schema::create('idbi_blacklisted_ips', function (Blueprint $table) {
            // Primary key
            $table->uuid('id')->primary();
            
            // IP address information
            $table->string('ip_address', 45)->unique(); // IP address (IPv4/IPv6)
            $table->string('ip_range', 100)->nullable(); // IP range (CIDR notation)
            $table->enum('ip_version', ['ipv4', 'ipv6'])->default('ipv4'); // IP version
            $table->string('subnet_mask', 45)->nullable(); // Subnet mask
            
            // Blacklist details
            $table->enum('blacklist_type', [
                'manual', 'automatic', 'failed_login', 'suspicious_activity', 
                'security_threat', 'spam', 'abuse', 'malware'
            ])->default('manual');
            $table->text('reason'); // Reason for blacklisting
            $table->text('description')->nullable(); // Detailed description
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            
            // Blacklist status
            $table->boolean('is_active')->default(true); // Blacklist active status
            $table->boolean('is_permanent')->default(false); // Permanent blacklist
            $table->timestamp('blacklisted_at'); // Blacklist timestamp
            $table->timestamp('expires_at')->nullable(); // Expiration timestamp
            $table->integer('duration_hours')->nullable(); // Blacklist duration
            
            // Geographic information
            $table->string('country', 100)->nullable(); // Country
            $table->string('country_code', 3)->nullable(); // Country code
            $table->string('region', 100)->nullable(); // Region/state
            $table->string('city', 100)->nullable(); // City
            $table->decimal('latitude', 10, 8)->nullable(); // Latitude
            $table->decimal('longitude', 11, 8)->nullable(); // Longitude
            $table->string('timezone', 50)->nullable(); // Timezone
            $table->string('isp', 255)->nullable(); // Internet Service Provider
            $table->string('organization', 255)->nullable(); // Organization
            
            // Threat intelligence
            $table->json('threat_indicators')->nullable(); // Threat indicators
            $table->integer('threat_score')->nullable(); // Threat score (0-100)
            $table->json('threat_sources')->nullable(); // Threat intelligence sources
            $table->boolean('is_known_threat')->default(false); // Known threat actor
            $table->json('malware_signatures')->nullable(); // Malware signatures
            
            // Activity tracking
            $table->integer('failed_login_count')->default(0); // Failed login attempts
            $table->integer('suspicious_activity_count')->default(0); // Suspicious activities
            $table->integer('total_requests')->default(0); // Total requests from IP
            $table->timestamp('first_seen_at')->nullable(); // First activity timestamp
            $table->timestamp('last_seen_at')->nullable(); // Last activity timestamp
            $table->timestamp('last_activity_at')->nullable(); // Last recorded activity
            
            // User association
            $table->uuid('associated_user_id')->nullable(); // Associated user
            $table->json('affected_users')->nullable(); // Users affected by this IP
            $table->string('user_agent', 1000)->nullable(); // Last known user agent
            $table->json('session_data')->nullable(); // Session information
            
            // Blacklist management
            $table->uuid('blacklisted_by'); // Who blacklisted the IP
            $table->uuid('approved_by')->nullable(); // Who approved the blacklist
            $table->timestamp('approved_at')->nullable(); // Approval timestamp
            $table->uuid('removed_by')->nullable(); // Who removed the blacklist
            $table->timestamp('removed_at')->nullable(); // Removal timestamp
            $table->text('removal_reason')->nullable(); // Removal reason
            
            // Whitelist override
            $table->boolean('has_whitelist_override')->default(false); // Whitelist override
            $table->uuid('whitelisted_by')->nullable(); // Who whitelisted
            $table->timestamp('whitelisted_at')->nullable(); // Whitelist timestamp
            $table->text('whitelist_reason')->nullable(); // Whitelist reason
            $table->timestamp('whitelist_expires_at')->nullable(); // Whitelist expiration
            
            // Monitoring and alerts
            $table->boolean('monitor_activity')->default(true); // Monitor IP activity
            $table->boolean('alert_on_activity')->default(false); // Alert on activity
            $table->json('alert_recipients')->nullable(); // Alert recipients
            $table->timestamp('last_alert_sent_at')->nullable(); // Last alert timestamp
            $table->integer('alert_count')->default(0); // Number of alerts sent
            
            // Bypass and exceptions
            $table->json('bypass_rules')->nullable(); // Bypass rules
            $table->boolean('allow_api_access')->default(false); // Allow API access
            $table->boolean('allow_admin_access')->default(false); // Allow admin access
            $table->json('exception_rules')->nullable(); // Exception rules
            
            // Rate limiting
            $table->integer('rate_limit_requests')->nullable(); // Rate limit (requests)
            $table->integer('rate_limit_window')->nullable(); // Rate limit window (seconds)
            $table->boolean('enforce_rate_limit')->default(true); // Enforce rate limiting
            $table->json('rate_limit_data')->nullable(); // Rate limiting data
            
            // Incident tracking
            $table->string('incident_id', 255)->nullable(); // Related incident ID
            $table->json('incident_data')->nullable(); // Incident information
            $table->boolean('is_part_of_attack')->default(false); // Part of coordinated attack
            $table->string('attack_signature', 255)->nullable(); // Attack signature
            
            // Evidence and forensics
            $table->json('evidence_data')->nullable(); // Evidence collected
            $table->json('log_entries')->nullable(); // Related log entries
            $table->json('network_data')->nullable(); // Network analysis data
            $table->longText('forensic_notes')->nullable(); // Forensic analysis notes
            
            // Compliance and legal
            $table->boolean('legal_hold')->default(false); // Legal hold status
            $table->text('legal_notes')->nullable(); // Legal notes
            $table->json('compliance_data')->nullable(); // Compliance information
            $table->boolean('reported_to_authorities')->default(false); // Reported to authorities
            
            // Review and validation
            $table->boolean('requires_review')->default(false); // Needs manual review
            $table->uuid('reviewed_by')->nullable(); // Who reviewed
            $table->timestamp('reviewed_at')->nullable(); // Review timestamp
            $table->text('review_notes')->nullable(); // Review notes
            $table->enum('review_status', ['pending', 'approved', 'rejected'])->nullable();
            
            // Data retention
            $table->boolean('is_archived')->default(false); // Archived status
            $table->timestamp('archived_at')->nullable(); // Archive timestamp
            $table->timestamp('delete_after')->nullable(); // Auto-delete timestamp
            $table->boolean('can_be_deleted')->default(true); // Deletion allowed
            
            // Audit fields
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('associated_user_id')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('blacklisted_by')->references('id')->on('idbi_users')->onDelete('restrict');
            $table->foreign('approved_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('removed_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('whitelisted_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('reviewed_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('idbi_users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('idbi_users')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['is_active', 'expires_at']);
            $table->index(['blacklist_type', 'is_active']);
            $table->index(['severity', 'is_active']);
            $table->index(['country_code']);
            $table->index(['is_permanent']);
            $table->index(['threat_score']);
            $table->index(['is_known_threat']);
            $table->index(['associated_user_id']);
            $table->index(['blacklisted_by']);
            $table->index(['has_whitelist_override']);
            $table->index(['monitor_activity']);
            $table->index(['requires_review']);
            $table->index(['review_status']);
            $table->index(['is_archived']);
            $table->index(['created_by']);
            $table->index(['updated_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_blacklisted_ips');
    }
};