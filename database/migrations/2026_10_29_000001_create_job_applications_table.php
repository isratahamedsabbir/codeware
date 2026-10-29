<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_post_id')->constrained('job_posts')->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 50);
            $table->text('cover_letter')->nullable();
            // Private disk path — never web-reachable, served only by the admin download route.
            $table->string('resume_path');
            $table->string('resume_original_name');
            $table->string('status', 20)->default('pending'); // pending | reviewed | shortlisted | rejected
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
