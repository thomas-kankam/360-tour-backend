<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->uuid('notification_uuid')->primary();
            $table->string('recipient_type', 20);
            $table->string('recipient_slug');
            $table->string('type', 64);
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('action_url', 2048)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['recipient_type', 'recipient_slug', 'read_at']);
            $table->index(['recipient_type', 'recipient_slug', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};
