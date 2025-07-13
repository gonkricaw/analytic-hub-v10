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
        Schema::create('idbi_widgets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('title');
            $table->text('description')->nullable();
            $table->uuid('widget_type_id');
            $table->uuid('dashboard_id');
            $table->uuid('created_by');
            $table->integer('position_x')->default(0);
            $table->integer('position_y')->default(0);
            $table->integer('width')->default(4);
            $table->integer('height')->default(3);
            $table->boolean('is_active')->default(true);
            $table->integer('refresh_interval')->default(300);
            $table->json('settings')->nullable();
            $table->string('data_source')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('widget_type_id')->references('id')->on('idbi_widget_types')->onDelete('cascade');
            $table->foreign('dashboard_id')->references('id')->on('idbi_dashboards')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('idbi_users')->onDelete('cascade');
            $table->index(['dashboard_id', 'is_active']);
            $table->index(['widget_type_id']);
            $table->index(['position_x', 'position_y']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_widgets');
    }
};
