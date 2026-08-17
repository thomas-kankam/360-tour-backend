<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('fullname');
            $table->string('email');
            $table->string('phone_number')->nullable();
            $table->text('message');
            $table->string('status')->default('new');
            $table->string('type')->default('general');
            $table->string('client_slug')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('client_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
