<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The individual skills of the portfolio theme's #technology section, edited
     * at /admin/portfolio/skills. A flat list rather than one table per skill
     * group, so adding a group from the admin needs no migration — `group` is
     * the free-text heading the card is filed under.
     */
    public function up(): void
    {
        Schema::create('portfolio_skills', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('group'); // e.g. 'Backend', 'Frontend & Tools', 'DevOps & Cloud'
            $table->string('icon')->nullable();
            $table->json('description')->nullable();
            $table->string('status', 20)->default('inactive');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'group', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portfolio_skills');
    }
};
