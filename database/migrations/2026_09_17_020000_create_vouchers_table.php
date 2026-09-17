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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('slug')->unique();
            $table->decimal('price', 10, 2)->default(0); // what the customer pays
            $table->decimal('value', 10, 2)->default(0); // face value of the issued voucher
            $table->string('currency', 3)->default('BDT');
            $table->unsignedSmallInteger('valid_days')->nullable(); // null = never expires
            $table->string('status', 20)->default('inactive');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
