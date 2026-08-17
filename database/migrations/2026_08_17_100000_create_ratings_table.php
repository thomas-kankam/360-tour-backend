<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->uuid('rating_uuid')->unique();
            $table->string('tour_slug');
            $table->string('client_slug');
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->string('status')->default('pending');
            $table->softDeletes();
            $table->timestamps();

            $table->index('tour_slug');
            $table->index('client_slug');
            $table->index('status');
            $table->unique(['tour_slug', 'client_slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
