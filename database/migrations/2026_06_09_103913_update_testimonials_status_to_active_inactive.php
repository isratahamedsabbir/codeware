<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('testimonials')
            ->where('status', 'published')
            ->update(['status' => 'active']);
        DB::table('testimonials')
            ->where('status', 'draft')
            ->update(['status' => 'inactive']);

        Schema::table('testimonials', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->change();
        });
    }

    public function down(): void
    {
        DB::table('testimonials')
            ->where('status', 'active')
            ->update(['status' => 'published']);
        DB::table('testimonials')
            ->where('status', 'inactive')
            ->update(['status' => 'draft']);

        Schema::table('testimonials', function (Blueprint $table) {
            $table->string('status', 20)->default('published')->change();
        });
    }
};
