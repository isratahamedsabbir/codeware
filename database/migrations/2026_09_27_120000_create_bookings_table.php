<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            // The service is the subject of the request, so a booking cannot
            // exist without one. Services are soft-deleted, so this only fires
            // on a hard delete - and a booking whose service is gone has nothing
            // left to say, so the rows go with it rather than piling up as
            // orphans pointing at an id nothing resolves.
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();

            // Collected on the storefront, so the columns are named for what the
            // visitor was asked rather than for a CRM. Only the name, an email and
            // the chosen service are required; a phone number and a note are the
            // two things a visitor can reasonably not have to hand over.
            $table->string('full_name');
            $table->string('email');
            $table->string('phone_number')->nullable();
            $table->text('message')->nullable();

            // Deliberately not a date the visitor picks. This records a request to
            // be contacted, and the date gets agreed afterwards - storing a
            // visitor-chosen date would read as a confirmed appointment, which is
            // a promise nothing here is set up to keep.
            $table->string('status', 20)->default('new');

            $table->timestamps();
            $table->softDeletes();

            // The admin list is the new-first, filterable one, and the unread
            // count reads the same column.
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
