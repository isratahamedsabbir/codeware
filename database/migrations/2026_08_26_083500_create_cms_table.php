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
            $table->json('titles')->nullable();
            $table->json('descriptions')->nullable();
            $table->json('buttons')->nullable();
            $table->json('cards')->nullable();
            $table->json('images')->nullable();
            $table->string('bg_image')->nullable();
            $table->string('status', 20)->default('active');
            $table->unsignedInteger('sort_order')->default(0);
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
