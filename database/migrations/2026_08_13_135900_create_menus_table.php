<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);
            $table->string('slug', 60)->unique();
            $table->timestamps();
        });

        // The admin sidebar's menu always exists, even before it has any items —
        // see App\Models\MenuItem::GROUP_ADMIN_SIDEBAR.
        DB::table('menus')->insert([
            'name' => 'Admin Menu',
            'slug' => 'admin-sidebar',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
