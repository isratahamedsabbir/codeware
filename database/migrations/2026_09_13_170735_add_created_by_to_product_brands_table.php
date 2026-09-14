<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A standalone migration rather than folded into
 * 2026_05_28_081050_create_product_brands_table.php — see
 * 2026_09_13_170734_add_created_by_to_categories_table.php for why.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_brands', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('product_brands', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
