<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE pages MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active'");

        DB::table('pages')->where('status', 'published')->update(['status' => 'active']);
        DB::table('pages')->where('status', 'draft')->update(['status' => 'inactive']);
    }

    public function down(): void
    {
        DB::table('pages')->where('status', 'active')->update(['status' => 'published']);
        DB::table('pages')->where('status', 'inactive')->update(['status' => 'draft']);

        DB::statement("ALTER TABLE pages MODIFY COLUMN status ENUM('draft','published') NOT NULL DEFAULT 'draft'");
    }
};
