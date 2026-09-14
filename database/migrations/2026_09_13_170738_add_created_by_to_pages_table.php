<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A standalone migration rather than folded into
 * 2026_05_28_120000_create_pages_table.php — see
 * 2026_09_13_170734_add_created_by_to_categories_table.php for why.
 *
 * A new column rather than reusing the existing user_id — that one is
 * overwritten on every save (see Page::booted()'s updating hook and every
 * Form that calls Page::updateOrCreate() with 'user_id' => auth()->id()),
 * so it tracks the most recent editor, not who originally created the page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
