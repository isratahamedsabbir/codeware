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
            // Discriminator column — one unified taxonomy table serving
            // post_category, product_category, brand and tag rows alike.
            $table->string('type', 20)->index();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->json('name');
            // Only ever set for tag rows (type = 'tag'); NULL for every other
            // kind, so the unique index never collides — see the Tag model.
            $table->string('slug')->nullable()->unique();
            $table->json('description')->nullable();
            $table->string('icon')->nullable();
            // Brand logos only — see the ProductBrand model.
            $table->string('logo')->nullable();
            $table->string('status', 20)->default('active');
            $table->unsignedSmallInteger('sort_order')->default(0);
            // Brand soft-deletes only — see the ProductBrand model.
            $table->softDeletes();
            $table->timestamps();

            $table->index(['type', 'parent_id', 'sort_order']);
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
