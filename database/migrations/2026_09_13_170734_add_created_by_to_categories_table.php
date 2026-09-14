<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A standalone migration rather than folded into
 * 2026_05_27_225520_create_categories_table.php (this project's usual "one
 * migration per table" convention) — replaying that migration via
 * migrate:fresh would wipe every other table too, discarding real data
 * already created through the admin panel. An additive column here avoids
 * that, same reasoning as 2026_09_13_124746_add_status_to_roles_table.php.
 *
 * Shared by both ProductCategory and PostCategory (App\Concerns\HasCreator)
 * — they're separate model classes but the same underlying `categories`
 * table, discriminated by `type`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('parent_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
