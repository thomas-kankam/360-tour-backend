<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_requests', function (Blueprint $table) {
            $table->uuid('request_uuid')->primary();
            $table->string('client_slug');
            $table->string('type', 20);
            $table->text('message');
            $table->string('status', 20)->default('pending');
            $table->text('admin_response')->nullable();
            $table->uuid('invoice_uuid')->nullable();
            $table->string('admin_slug')->nullable();
            $table->timestamps();

            $table->index(['client_slug', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_requests');
    }
};
