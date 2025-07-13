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
        Schema::create('idbi_widget_configurations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('widget_id');
            $table->string('key');
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->boolean('is_encrypted')->default(false);
            $table->text('description')->nullable();
            $table->json('validation_rules')->nullable();
            $table->text('default_value')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('widget_id')->references('id')->on('idbi_widgets')->onDelete('cascade');
            $table->unique(['widget_id', 'key']);
            $table->index(['widget_id']);
            $table->index(['key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idbi_widget_configurations');
    }
};
