<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE posts MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'inactive'");

        DB::table('posts')->where('status', 'draft')->update(['status' => 'inactive']);
        DB::table('posts')->where('status', 'published')->update(['status' => 'active']);
    }

    public function down(): void
    {
        DB::table('posts')->where('status', 'active')->update(['status' => 'published']);
        DB::table('posts')->where('status', 'inactive')->update(['status' => 'draft']);

        DB::statement("ALTER TABLE posts MODIFY COLUMN status ENUM('draft', 'published') NOT NULL DEFAULT 'draft'");
    }
};
