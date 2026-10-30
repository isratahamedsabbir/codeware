<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Named job_posts, not jobs: Laravel's database queue already owns `jobs`.
    public function up(): void
    {
        Schema::create('job_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('department')->nullable();
            $table->string('location')->nullable();
            $table->string('employment_type', 30)->default('full-time'); // full-time | part-time | contract | internship
            $table->unsignedSmallInteger('vacancy')->default(1);
            $table->date('deadline')->nullable();
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->string('status', 20)->default('draft'); // draft | open | closed
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_posts');
    }
};
