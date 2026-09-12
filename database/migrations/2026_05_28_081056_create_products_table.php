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
            $table->foreignId('brand_id')->nullable()->constrained('product_brands')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('product_vendors')->nullOnDelete();
            $table->string('sku')->nullable()->unique();
            $table->json('name');
            $table->json('excerpt')->nullable();
            $table->json('description')->nullable();
            $table->json('specifications')->nullable();
            $table->json('benefits')->nullable();
            $table->json('usage_instructions')->nullable();
            $table->json('variations')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('datasheet_url')->nullable();
            $table->string('status', 20)->default('active');
            $table->string('product_type', 20)->default('physical');
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->boolean('charge_shipping')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_upcoming')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
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
