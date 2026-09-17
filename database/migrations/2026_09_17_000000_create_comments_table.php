<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            // Post, Product, or Service — polymorphic rather than three near-
            // identical tables, mirroring the faqs table's faqable pattern.
            $table->string('commentable_type');
            $table->unsignedBigInteger('commentable_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            // Self-referencing, one level deep only (a reply can't itself be
            // replied to — enforced in CommentController::store(), not here).
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $table->text('body');
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->index(['commentable_type', 'commentable_id', 'status']);
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
