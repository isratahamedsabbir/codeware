<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');
            $table->text('shipping_address');
            // Fulfillment progress — separate from payment_status below, since an
            // order can be e.g. "processing" while payment is still "pending" (COD).
            $table->string('status', 20)->default('pending');
            $table->string('payment_method', 30);
            $table->string('payment_status', 20)->default('pending');
            $table->string('currency', 3)->default('BDT');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('total', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['payment_status', 'created_at']);
            $table->index(['payment_method', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
