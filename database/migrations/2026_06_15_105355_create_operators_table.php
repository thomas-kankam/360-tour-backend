<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operators', function (Blueprint $table) {
            $table->id();
            $table->string('operator_slug')->unique();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->string('phone_number')->nullable();
            $table->string('organization')->nullable();
            $table->string('location')->nullable();
            $table->string('status')->default('inactive');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('operator_slug', 'operators_operator_slug_idx');
            $table->index('email', 'operators_email_idx');
            $table->index('status', 'operators_status_idx');
            $table->index('deleted_at', 'operators_deleted_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operators');
    }
};
