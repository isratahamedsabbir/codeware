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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->index();
            $table->json('name');
            $table->string('slug');
            $table->json('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('status', 20)->default('active');
            $table->json('seo_title')->nullable();
            $table->json('seo_description')->nullable();
            $table->string('og_image')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['type', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
