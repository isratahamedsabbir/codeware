<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A standalone migration rather than folded into
 * 2026_08_11_122625_create_permission_tables.php (that migration recreates
 * spatie's roles table from scratch — replaying it via migrate:fresh would
 * wipe every other table too), same reasoning as
 * 2026_09_13_124746_add_status_to_roles_table.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
