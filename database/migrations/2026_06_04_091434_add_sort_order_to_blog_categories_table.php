<?php

use App\Models\BlogCategory;
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
        Schema::table('blog_categories', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('description');
        });

        BlogCategory::query()->orderBy('id')->each(function (BlogCategory $category, $index) {
            $category->sort_order = $index;
            $category->save();
        });
    }

    public function down(): void
    {
        Schema::table('blog_categories', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
