<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Auto-generated AD-XXXXXXXX business code (see HasUniqueCode).
            $table->string('code')->unique();
            $table->string('image')->nullable();
            $table->string('url', 2048)->nullable();
            $table->unsignedBigInteger('clicks')->default(0);
            // Validity window — when both are null the ad always runs.
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};