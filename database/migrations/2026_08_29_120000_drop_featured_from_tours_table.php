<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tours', 'featured')) {
            return;
        }

        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn('featured');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('tours', 'featured')) {
            return;
        }

        Schema::table('tours', function (Blueprint $table) {
            $table->boolean('featured')->default(false)->after('status');
            $table->index('featured');
        });
    }
};
