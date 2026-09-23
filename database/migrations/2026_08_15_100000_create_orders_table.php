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
            // Nullable — a guest checkout (the public order API requires no
            // account) has no user to attach; only once the customer signs in
            // do we know who this order belongs to. The account page falls back
            // to matching customer_email for orders placed before sign-in.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');
            // Nullable — a service-only order (nothing to deliver) can omit it;
            // still required whenever the cart contains a physical product
            // (see OrderController::store()'s conditional validation rule).
            $table->text('shipping_address')->nullable();
            // Fulfillment progress — separate from payment_status below, since an
            // order can be e.g. "processing" while payment is still "pending" (COD).
            $table->string('status', 20)->default('pending');
            $table->string('payment_method', 30);
            $table->string('payment_status', 20)->default('pending');
            $table->string('currency', 3)->default('BDT');
            $table->decimal('subtotal', 10, 2);
            // Applied coupon code and its discount (0.00 when none). Params are
            // server-computed server-side, never trusted from the client.
            $table->string('coupon_code', 50)->nullable();
            $table->decimal('discount', 10, 2)->default(0);
            // VAT snapshot — the tax amount (and the rate it was computed at)
            // applied to this order when Settings → Currency → VAT is enabled.
            // Rate is stored too, so a later rate change never rewrites history.
            $table->decimal('vat_amount', 10, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->nullable();
            // Shipping snapshot — the chosen delivery method's name and cost at
            // order time. Snapshotted (no FK) like vat_amount/vat_rate, so a
            // method's later rename, price change or deletion never rewrites an
            // order's history. 0.00 on orders with nothing physical to deliver.
            $table->string('shipping_method')->nullable();
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['payment_status', 'created_at']);
            $table->index(['payment_method', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
