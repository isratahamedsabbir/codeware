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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->json('name');
            $table->string('slug')->unique();
            $table->json('excerpt')->nullable();
            $table->json('description')->nullable();
            $table->json('specifications')->nullable();
            $table->json('benefits')->nullable();
            $table->json('usage_instructions')->nullable();
            $table->json('faq')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('og_image')->nullable();
            $table->string('datasheet_url')->nullable();
            $table->string('status', 20)->default('active');
            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('seo_title')->nullable();
            $table->json('seo_description')->nullable();
            $table->json('puck_data')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
