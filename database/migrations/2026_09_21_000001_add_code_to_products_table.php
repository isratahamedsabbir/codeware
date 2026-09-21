<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * A standalone migration rather than folded into
 * 2026_05_28_081056_create_products_table.php (this project's usual "one
 * migration per table" convention) — replaying that migration via
 * migrate:fresh would wipe every other table too, discarding real data
 * already created through the admin panel. An additive column here avoids
 * that, same reasoning as 2026_09_13_144421_add_is_blocked_to_users_table.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('code', 20)->nullable()->unique()->after('sku');
        });

        // Backfill existing products — each row gets its own PRD-XXXXXXXX code
        // (see App\Concerns\HasUniqueCode).
        DB::table('products')->select('id')->orderBy('id')->eachById(function ($product) {
            do {
                $code = 'PRD-'.strtoupper(Str::random(8));
            } while (DB::table('products')->where('code', $code)->exists());

            DB::table('products')->where('id', $product->id)->update(['code' => $code]);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
