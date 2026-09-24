<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            // Nullable: a null type means the row is shared across both the
            // post and product pools (tag/brand), same as the legacy 'tag'
            // pool but without a placeholder string.
            $table->string('type', 20)->nullable()->index();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('icon')->nullable();
            // Brand logos only — see the ProductBrand model.
            $table->string('logo')->nullable();
            $table->string('status', 20)->default('active');
            $table->unsignedSmallInteger('sort_order')->default(0);
            // Featured product categories surface on the storefront homepage's
            // "Shop by category" grid (see the ecommerce home view).
            $table->boolean('featured')->default(false);
            // Brand soft-deletes only — see the ProductBrand model.
            $table->softDeletes();
            $table->timestamps();

            $table->index(['type', 'parent_id', 'sort_order']);
        });

        // Previously brands used a single 'brand' discriminator; they now
        // live in post/product pools (post_brand / product_brand) like tags,
        // so legacy brand rows are moved into the product pool from which
        // they were always created. No-op on fresh databases where no brand
        // rows exist yet.
        DB::table('categories')->where('type', 'brand')->update(['type' => 'product_brand']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
