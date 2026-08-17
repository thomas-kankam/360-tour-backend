<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tours', function (Blueprint $table) {
            $table->id();
            $table->string('tour_slug')->unique();
            $table->string('name');
            $table->json('locations')->nullable();
            $table->string('country')->nullable();
            $table->string('country_code')->nullable();
            $table->json('categories')->nullable();
            $table->string('status')->default('draft');
            $table->boolean('featured')->default(false);
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->string('duration_label')->nullable();
            $table->unsignedSmallInteger('group_size_min')->nullable();
            $table->unsignedSmallInteger('group_size_max')->nullable();
            $table->string('group_size_label')->nullable();
            $table->decimal('price_amount', 12, 2)->default(0);
            $table->string('price_currency', 10)->default('USD');
            $table->string('price_label')->nullable();
            $table->string('badge')->nullable();
            $table->string('badge_variant')->nullable();
            $table->longText('cover_image_url')->nullable();
            $table->json('gallery_image_urls')->nullable();
            $table->longText('description')->nullable();
            $table->json('highlights')->nullable();
            $table->json('itinerary')->nullable();
            $table->json('included')->nullable();
            $table->json('not_included')->nullable();
            $table->json('departure_dates')->nullable();
            $table->json('booking_settings')->nullable();
            $table->string('created_by_admin_slug')->nullable();
            $table->string('admin_slug')->nullable();
            $table->unsignedInteger('booking_count')->default(0);
            $table->decimal('rating', 3, 1)->default(0);
            $table->unsignedInteger('review_count')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index('tour_slug');
            $table->index('status');
            $table->index('featured');
            $table->index('admin_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tours');
    }
};
