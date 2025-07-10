<?php

// Purpose: Create password history tracking table for last 5 passwords
// Related Feature: Password Management - Password History Tracking
// Dependencies: idbi_users table must exist

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates table to track user password history (last 5 passwords)
     * Prevents password reuse as per security requirements
     */
    public function up(): void
    {
        Schema::create('idbi_password_histories', function (Blueprint $table) {
            // Primary key using UUID
            $table->uuid('id')->primary();
            
            // Foreign key to users table
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('idbi_users')->onDelete('cascade');
            
            // Password hash storage
            $table->string('password_hash');
            
            // Timestamps for tracking when password was set
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_password_histories');
    }
};
