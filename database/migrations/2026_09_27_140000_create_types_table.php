<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('types', function (Blueprint $table) {
            $table->id();

            // Translatable, like every other name in the taxonomy — a Type is
            // shown in a dropdown next to a category/brand/tag list, so it has to
            // read in whichever locale the admin is working in.
            $table->json('name');

            // The stable key the app scopes by. `categories.type_id` is an FK, so
            // nothing queries this column directly — but the models need a
            // hardcoded, readable way to say "the product pool" / "the post pool"
            // without a lookup table, which is what these two slugs are for (see
            // App\Models\Type::PRODUCT / ::POST). Uniquely indexed so a
            // subquery narrowing `categories.type_id` to one pool is a single
            // cheap index lookup.
            $table->string('slug', 30)->unique();

            $table->string('status', 20)->default('active');
            $table->unsignedSmallInteger('sort_order')->default(0);

            // Soft-deleted rather than cascaded away from: a hard-deleted Type
            // would leave every category, brand and tag that points at it
            // unassignable, so the admin can always undo a delete.
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('types');
    }
};
