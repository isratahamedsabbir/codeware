<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A standalone migration rather than folded into
 * 2026_05_28_081056_create_products_table.php (this project's usual "one
 * migration per table" convention) — replaying that migration via
 * migrate:fresh would wipe every other table too, discarding real data
 * already created through the admin panel. An additive column here avoids
 * that, same reasoning as 2026_09_13_124746_add_status_to_roles_table.php.
 *
 * Posts already track their author via posts.user_id (see Post::user()) —
 * this gives Product the same thing, named created_by since "user" on a
 * product would be ambiguous (vendor vs. admin vs. customer).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('vendor_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
