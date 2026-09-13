<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A standalone migration rather than folded into
 * 2026_08_11_122625_create_permission_tables.php (this project's usual "one
 * migration per table" convention) — that migration recreates spatie's roles
 * table from scratch, and replaying it via migrate:fresh would wipe every
 * other table too, discarding real data already created through the admin
 * panel. An additive column here avoids that.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('guard_name');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
