<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A standalone migration rather than folded into
 * 0001_01_01_000000_create_users_table.php (this project's usual "one
 * migration per table" convention) — replaying that migration via
 * migrate:fresh would wipe every other table too, discarding real data
 * already created through the admin panel. An additive column here avoids
 * that, same reasoning as 2026_09_13_124746_add_status_to_roles_table.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_blocked')->default(false)->after('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_blocked');
        });
    }
};
