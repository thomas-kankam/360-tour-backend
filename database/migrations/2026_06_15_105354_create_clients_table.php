<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('client_slug')->unique();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('email')->unique();
            $table->string('location')->nullable();
            $table->string('status')->default('inactive');
            $table->longText('profile_image')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // indexes
            $table->index('client_slug', 'clients_client_slug_idx');
            $table->index('email', 'clients_email_idx');
            $table->index('status', 'clients_status_idx');
            $table->index('deleted_at', 'clients_deleted_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
