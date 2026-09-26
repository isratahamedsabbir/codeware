<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The "Projects" cards of the portfolio theme's #projects section, edited at
     * /admin/portfolio/projects. One table per portfolio section rather than a
     * generic "content" table: the three lists have genuinely different columns,
     * and a portfolio is a single deployment's concern, not a reusable module.
     *
     * No slug column — a project card is never routed on its own, its `link`
     * points straight at the live site or repository.
     */
    public function up(): void
    {
        Schema::create('portfolio_projects', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('description')->nullable();
            $table->string('icon')->nullable(); // emoji shown in the card's corner
            $table->json('tech')->nullable(); // plain list of technology names, e.g. ["Laravel", "Redis"]
            $table->string('stats')->nullable(); // the small badge, e.g. "Open Source"
            $table->string('link')->nullable(); // external URL for the card's "View Project" link
            $table->string('status', 20)->default('inactive');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portfolio_projects');
    }
};
