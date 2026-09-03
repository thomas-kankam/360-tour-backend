<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('experiences')) {
            return;
        }

        Schema::create('experiences', function (Blueprint $table) {
            $table->id();
            $table->string('experience_key', 80)->unique();
            $table->string('slug')->unique();
            $table->string('label');
            $table->string('icon_key', 40)->default('compass');
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->json('highlights')->nullable();
            $table->json('regions')->nullable();
            $table->json('keywords')->nullable();
            $table->text('image')->nullable();
            $table->string('badge_text', 80)->nullable();
            $table->json('tour_query')->nullable();
            $table->string('story_category', 64)->nullable();
            $table->json('related_story_slugs')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['status', 'sort_order'], 'experiences_status_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiences');
    }
};
