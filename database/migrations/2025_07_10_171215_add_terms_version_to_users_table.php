<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds terms_version_accepted field to track which version of T&C
     * each user has accepted for the notification system.
     */
    public function up(): void
    {
        Schema::table('idbi_users', function (Blueprint $table) {
            $table->string('terms_version_accepted', 20)->nullable()->after('terms_accepted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('idbi_users', function (Blueprint $table) {
            $table->dropColumn('terms_version_accepted');
        });
    }
};
