<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The timeline entries of the portfolio theme's #experience section, edited
     * at /admin/portfolio/experiences. Rendered newest-first by sort_order.
     *
     * `period` stays a plain string rather than a pair of dates: portfolios
     * routinely show things a date range can't express ("2024 - Present",
     * "Summer 2023"), and the theme only ever prints it verbatim.
     */
    public function up(): void
    {
        Schema::create('portfolio_experiences', function (Blueprint $table) {
            $table->id();
            $table->json('role');
            $table->json('company')->nullable();
            $table->string('period')->nullable();
            $table->json('description')->nullable();
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
        Schema::dropIfExists('portfolio_experiences');
    }
};
