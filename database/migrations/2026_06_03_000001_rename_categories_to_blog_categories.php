<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
        });

        Schema::rename('categories', 'blog_categories');

        Schema::table('posts', function (Blueprint $table) {
            $table->foreign('category_id')->references('id')->on('blog_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
        });

        Schema::rename('blog_categories', 'categories');

        Schema::table('posts', function (Blueprint $table) {
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
        });
    }
};
