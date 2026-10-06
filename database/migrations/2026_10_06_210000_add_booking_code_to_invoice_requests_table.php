<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_requests', function (Blueprint $table) {
            $table->string('booking_code')->nullable()->after('invoice_uuid');
            $table->index('booking_code');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_requests', function (Blueprint $table) {
            $table->dropIndex(['booking_code']);
            $table->dropColumn('booking_code');
        });
    }
};
