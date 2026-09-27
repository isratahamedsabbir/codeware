<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            // Nullable only so nullOnDelete can keep the line on a receipt when
            // the product is later deleted — the price and name are snapshotted,
            // so the sale stays readable ("product removed"). Every order line
            // is a product: a service is booked, not ordered, so there is no
            // second kind of line to discriminate against.
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            // Snapshotted at order time — a later price/name change on the
            // product must not rewrite what the customer actually ordered and
            // paid for.
            $table->string('item_name');
            // The exact SKU at order time — a variant combination's own sku
            // when the product uses options, else the product sku. Snapshotted
            // like item_name, so a later sku edit can't rewrite the receipt.
            $table->string('sku')->nullable();
            // The selected product variation (attribute name => value map) for
            // a variant order line, e.g. {"Color":"Red","Size":"M"}. Null for
            // base-product lines. The combination's price is already snapshotted
            // into unit_price; this preserves *which* option the customer picked.
            $table->json('variations')->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('line_total', 10, 2);
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
