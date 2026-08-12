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
            $table->string('booking_code')->nullable()->unique();
            $table->string('client_slug')->nullable();
            $table->string('booked_by_type');
            $table->string('booked_by_slug');
            $table->string('tour_slug');
            $table->string('booking_type')->default('group');
            $table->date('selected_date');
            $table->unsignedInteger('travelers')->default(1);
            $table->string('payment_mode');
            $table->string('payment_status')->default('pending');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->json('lead_traveler');
            $table->json('group_details')->nullable();
            $table->text('special_requests')->nullable();
            $table->text('dietary_needs')->nullable();
            $table->json('additional_travelers')->nullable();
            $table->string('status')->default('pending');
            $table->string('admin_slug')->nullable();
            $table->string('created_by_admin_slug')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('booking_code');
            $table->index('client_slug');
            $table->index('tour_slug');
            $table->index('payment_status');
            $table->index('admin_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
