<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE career_jobs MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'inactive'");

        DB::table('career_jobs')->where('status', 'draft')->update(['status' => 'inactive']);
        DB::table('career_jobs')->where('status', 'open')->update(['status' => 'active']);
        DB::table('career_jobs')->where('status', 'closed')->update(['status' => 'inactive']);
    }

    public function down(): void
    {
        DB::table('career_jobs')->where('status', 'active')->update(['status' => 'open']);
        DB::table('career_jobs')->where('status', 'inactive')->update(['status' => 'draft']);

        DB::statement("ALTER TABLE career_jobs MODIFY COLUMN status ENUM('draft', 'open', 'closed') NOT NULL DEFAULT 'draft'");
    }
};
