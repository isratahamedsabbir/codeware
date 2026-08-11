<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE videos MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active'");

        DB::table('videos')->where('status', 'draft')->update(['status' => 'inactive']);
        DB::table('videos')->where('status', 'published')->update(['status' => 'active']);
    }

    public function down(): void
    {
        DB::table('videos')->where('status', 'active')->update(['status' => 'published']);
        DB::table('videos')->where('status', 'inactive')->update(['status' => 'draft']);

        DB::statement("ALTER TABLE videos MODIFY COLUMN status ENUM('published', 'draft') NOT NULL DEFAULT 'draft'");
    }
};
