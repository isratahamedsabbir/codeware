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
        Schema::create('voucher_purchases', function (Blueprint $table) {
            $table->id();
            // nullOnDelete (not cascade): deleting a voucher product must never
            // erase the record of vouchers already sold from it.
            $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->nullOnDelete();
            $table->string('code')->unique();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone', 30)->nullable();
            $table->string('recipient_name')->nullable();
            $table->text('message')->nullable();
            $table->decimal('price_paid', 10, 2)->default(0);
            $table->decimal('value', 10, 2)->default(0);
            $table->string('currency', 3)->default('BDT');
            $table->string('status', 20)->default('issued');
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('purchased_at')->nullable();
            $table->timestamps();

            $table->index('customer_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher_purchases');
    }
};
