<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('career_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->json('title');
            $table->string('slug')->unique();
            $table->json('description')->nullable();
            $table->string('position');
            $table->unsignedSmallInteger('vacancy')->default(1);
            $table->date('deadline')->nullable();
            $table->string('location')->nullable();
            $table->enum('status', ['draft', 'open', 'closed'])->default('draft');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('career_jobs');
    }
};
