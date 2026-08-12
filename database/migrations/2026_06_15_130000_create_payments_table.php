<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_slug')->unique();
            $table->string('booking_code');
            $table->string('paystack_reference')->nullable()->unique();
            $table->string('paystack_access_code')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('GHS');
            $table->string('status')->default('pending');
            $table->text('payment_url')->nullable();
            $table->json('paystack_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('booking_code');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
