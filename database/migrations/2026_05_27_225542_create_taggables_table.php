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
        // Polymorphic pivot — one table shared by every taggable model (Post,
        // Product, ...) rather than a dedicated post_tag/product_tag table
        // each, mirroring the faqs/comments polymorphic pattern.
        Schema::create('taggables', function (Blueprint $table) {
            $table->foreignId('tag_id')->constrained('categories')->cascadeOnDelete();
            $table->morphs('taggable');
            $table->primary(['tag_id', 'taggable_id', 'taggable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taggables');
    }
};
