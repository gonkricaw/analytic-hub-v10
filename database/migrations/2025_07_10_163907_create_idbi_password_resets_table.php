<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('idbi_password_resets', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('token'); // Hashed UUID token
            $table->timestamp('created_at');
            $table->timestamp('expires_at')->index();
            $table->timestamp('used_at')->nullable();
            $table->boolean('used')->default(false)->index();
            $table->string('ip_address', 45)->nullable(); // Support IPv6
            $table->text('user_agent')->nullable();
            
            // Indexes for performance
            $table->index(['email', 'used']);
            $table->index(['expires_at', 'used']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_password_resets');
    }
};
