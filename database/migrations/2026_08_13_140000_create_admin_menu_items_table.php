<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('admin_menu_items')->nullOnDelete();
            $table->boolean('is_group')->default(false);
            $table->string('label');
            $table->string('icon', 64)->nullable();
            $table->string('route_name', 100)->nullable();
            $table->string('url', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_short_menu')->default(false);
            $table->timestamps();

            $table->index(['parent_id', 'sort_order']);
            $table->index(['is_active', 'sort_order']);
            $table->index(['is_short_menu', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_menu_items');
    }
};
