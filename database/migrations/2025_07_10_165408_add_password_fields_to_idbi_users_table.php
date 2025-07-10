<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds password management fields to support:
     * - Password expiry tracking
     * - Force password change functionality
     * - Enhanced user activity tracking
     */
    public function up(): void
    {
        Schema::table('idbi_users', function (Blueprint $table) {
            // Password expiry management
            $table->timestamp('password_expires_at')->nullable()->after('password_changed_at');
            
            // Force password change flag
            $table->boolean('force_password_change')->default(false)->after('password_expires_at');
            
            // Enhanced activity tracking
            $table->timestamp('last_seen_at')->nullable()->after('last_login_ip');
            $table->string('last_ip', 45)->nullable()->after('last_seen_at');
            
            // Add indexes for performance
            $table->index(['password_expires_at']);
            $table->index(['force_password_change']);
            $table->index(['last_seen_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('idbi_users', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['password_expires_at']);
            $table->dropIndex(['force_password_change']);
            $table->dropIndex(['last_seen_at']);
            
            // Drop columns
            $table->dropColumn([
                'password_expires_at',
                'force_password_change',
                'last_seen_at',
                'last_ip'
            ]);
        });
    }
};
