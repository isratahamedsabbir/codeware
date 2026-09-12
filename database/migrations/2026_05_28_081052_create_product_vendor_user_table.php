<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which users can access which vendors' portal (see App\Livewire\Vendor\*)
     * — many-to-many, since a user can be assigned to more than one vendor.
     */
    public function up(): void
    {
        Schema::create('product_vendor_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('product_vendors')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['vendor_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_vendor_user');
    }
};
