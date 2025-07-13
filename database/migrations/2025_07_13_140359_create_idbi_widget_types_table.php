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
        Schema::create('idbi_widget_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('category')->default('general');
            $table->boolean('is_active')->default(true);
            $table->integer('default_width')->default(4);
            $table->integer('default_height')->default(3);
            $table->integer('min_width')->default(2);
            $table->integer('min_height')->default(2);
            $table->integer('max_width')->default(12);
            $table->integer('max_height')->default(12);
            $table->integer('default_refresh_interval')->default(300);
            $table->json('configuration_schema')->nullable();
            $table->json('data_source_types')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['is_active', 'category']);
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_widget_types');
    }
};
