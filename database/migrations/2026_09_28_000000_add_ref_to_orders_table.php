<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A standalone migration rather than folded into
 * 2026_08_15_100000_create_orders_table.php (this project's usual "one
 * migration per table" convention) — replaying that migration via migrate:fresh
 * would wipe every other table too, discarding real orders already placed. Same
 * reasoning as 2026_09_24_000000_add_delivery_boy_to_orders_table.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // The account whose ?ref= link this order arrived through — the
            // referrer's own user id, not the USR- code from the URL, so
            // reporting is a join rather than a code lookup. Null for every
            // order that came from a direct visit, and nulled by the FK if the
            // referrer is ever deleted, so a removed account can never leave an
            // order pointing at a row that no longer exists.
            $table->foreignId('ref')->nullable()->after('delivery_boy_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ref');
        });
    }
};
