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
        // Categories
        Schema::table('categories', function (Blueprint $table) {
            $table->json('name')->change();
            $table->json('description')->nullable()->change();
        });

        // Tags
        Schema::table('tags', function (Blueprint $table) {
            $table->json('name')->change();
        });

        // Posts
        Schema::table('posts', function (Blueprint $table) {
            $table->json('title')->change();
            $table->json('excerpt')->nullable()->change();
            $table->json('content')->nullable()->change();
            $table->json('seo_title')->nullable()->change();
            $table->json('seo_description')->nullable()->change();
        });

        // Pages
        Schema::table('pages', function (Blueprint $table) {
            $table->json('title')->change();
            $table->json('content')->nullable()->change();
            $table->json('seo_title')->nullable()->change();
            $table->json('seo_description')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('name')->change();
            $table->text('description')->nullable()->change();
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->string('name')->change();
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->string('title')->change();
            $table->text('excerpt')->nullable()->change();
            $table->longText('content')->nullable()->change();
            $table->string('seo_title')->nullable()->change();
            $table->text('seo_description')->nullable()->change();
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->string('title')->change();
            $table->longText('content')->nullable()->change();
            $table->string('seo_title')->nullable()->change();
            $table->text('seo_description')->nullable()->change();
        });
    }
};
