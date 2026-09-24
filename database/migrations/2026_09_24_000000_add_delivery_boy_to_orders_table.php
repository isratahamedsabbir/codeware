<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A standalone migration rather than folded into
 * 2026_08_15_100000_create_orders_table.php (this project's usual "one
 * migration per table" convention) — replaying that migration via
 * migrate:fresh would wipe every other table too, discarding real orders
 * already placed. Same reasoning as
 * 2026_09_18_000000_add_is_delivery_boy_to_users_table.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // The delivery rider (a customer account with users.is_delivery_boy)
            // assigned from Admin → Orders → Show; they see only these orders
            // in the delivery portal (App\Livewire\Delivery\*).
            $table->foreignId('delivery_boy_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            // Set when the rider confirms delivery with the customer's emailed OTP.
            $table->timestamp('delivered_at')->nullable()->after('notes');

            $table->index(['delivery_boy_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['delivery_boy_id', 'status']);
            $table->dropConstrainedForeignId('delivery_boy_id');
            $table->dropColumn('delivered_at');
        });
    }
};
