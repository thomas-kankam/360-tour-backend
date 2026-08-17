<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('invoice_uuid')->unique();
            $table->string('invoice_number')->unique();
            $table->string('status')->default('draft');
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->string('reference')->nullable();
            $table->string('project')->nullable();
            $table->string('currency', 10)->default('USD');
            $table->decimal('tax_percent', 8, 2)->default(0);
            $table->decimal('discount_percent', 8, 2)->default(0);
            $table->decimal('shipping', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->text('payment_details')->nullable();
            $table->string('billed_to_name');
            $table->string('billed_to_email');
            $table->string('billed_to_phone')->nullable();
            $table->text('billed_to_address')->nullable();
            $table->string('client_slug')->nullable();
            $table->json('line_items')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('invoice_number');
            $table->index('status');
            $table->index('client_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
