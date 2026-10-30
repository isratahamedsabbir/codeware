<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per generated CRUD (Admin → CRUD → CRUD Builder). The real data
        // lives in its own `crud_*` table; this row is the definition that table,
        // the sidebar link and the records screen are all built from.
        Schema::create('cruds', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('table_name')->unique();
            $table->string('icon', 64)->default('rectangle-stack');
            // [{name, label, type, required, in_list, options}]
            $table->json('fields');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cruds');
    }
};
