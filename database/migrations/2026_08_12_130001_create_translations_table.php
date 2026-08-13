<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            // '*' holds the JSON-style keys used by __('Some string'). Any other value is a
            // group file, e.g. group 'auth' + key 'failed' backs __('auth.failed').
            $table->string('group', 100)->default('*');
            // 191 chars keeps (group, key, locale) inside MySQL's 3072-byte index limit.
            $table->string('key', 191);
            $table->string('locale', 10);
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['group', 'key', 'locale']);
            $table->index(['locale', 'group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
