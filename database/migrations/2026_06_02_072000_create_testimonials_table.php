<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('image', 255)->nullable();
            $table->json('name');
            $table->json('comment');
            $table->string('location', 255)->nullable();
            $table->string('type', 100)->nullable();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('published');
            $table->integer('sort_order')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
