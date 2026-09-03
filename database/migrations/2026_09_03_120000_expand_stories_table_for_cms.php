<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Expand the stub stories table. Additive only — safe on live DBs that already
 * have the empty `stories` table from 2026_06_15_105552.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            if (! Schema::hasColumn('stories', 'slug')) {
                $table->string('slug')->nullable()->after('id');
            }
            if (! Schema::hasColumn('stories', 'title')) {
                $table->string('title')->nullable()->after('slug');
            }
            if (! Schema::hasColumn('stories', 'excerpt')) {
                $table->text('excerpt')->nullable()->after('title');
            }
            if (! Schema::hasColumn('stories', 'category')) {
                $table->string('category', 64)->nullable()->after('excerpt');
            }
            if (! Schema::hasColumn('stories', 'country')) {
                $table->string('country', 120)->nullable()->after('category');
            }
            if (! Schema::hasColumn('stories', 'author')) {
                $table->string('author', 160)->nullable()->after('country');
            }
            if (! Schema::hasColumn('stories', 'author_role')) {
                $table->string('author_role', 160)->nullable()->after('author');
            }
            if (! Schema::hasColumn('stories', 'display_date')) {
                $table->string('display_date', 64)->nullable()->after('author_role');
            }
            if (! Schema::hasColumn('stories', 'read_time')) {
                $table->string('read_time', 40)->nullable()->after('display_date');
            }
            if (! Schema::hasColumn('stories', 'image')) {
                $table->text('image')->nullable()->after('read_time');
            }
            if (! Schema::hasColumn('stories', 'body')) {
                $table->json('body')->nullable()->after('image');
            }
            if (! Schema::hasColumn('stories', 'status')) {
                $table->string('status', 20)->default('draft')->after('body');
            }
            if (! Schema::hasColumn('stories', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('stories', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('published_at');
            }
        });

        try {
            Schema::table('stories', function (Blueprint $table) {
                $table->unique('slug', 'stories_slug_unique');
            });
        } catch (\Throwable $e) {
            // Index may already exist on re-run.
        }

        try {
            Schema::table('stories', function (Blueprint $table) {
                $table->index(['status', 'sort_order'], 'stories_status_sort_idx');
            });
        } catch (\Throwable $e) {
            // Index may already exist on re-run.
        }
    }

    public function down(): void
    {
        // Keep columns on rollback to avoid wiping live CMS content.
    }
};
