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
        Schema::create('cms', function (Blueprint $table) {
            $table->id();
            $table->string('page');
            $table->string('section');
            $table->unique(['page', 'section']);
            $table->json('title')->nullable();
            $table->json('description')->nullable();
            $table->json('buttons')->nullable();
            $table->json('cards')->nullable();
            $table->string('image')->nullable();
            $table->string('bg_image')->nullable();
            $table->string('status', 20)->default('active');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms');
    }
};
