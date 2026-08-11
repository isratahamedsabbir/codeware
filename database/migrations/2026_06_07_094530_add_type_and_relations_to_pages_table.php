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
        Schema::table('pages', function (Blueprint $table) {
            $table->string('type', 20)->default('page')->after('template');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete()->after('type');
            $table->foreignId('post_id')->nullable()->constrained('posts')->nullOnDelete()->after('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['post_id']);
            $table->dropColumn(['type', 'product_id', 'post_id']);
        });
    }
};
