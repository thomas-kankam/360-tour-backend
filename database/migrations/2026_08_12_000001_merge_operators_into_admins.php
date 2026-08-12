<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tours', 'operator_slug') && ! Schema::hasColumn('tours', 'admin_slug')) {
            Schema::table('tours', function (Blueprint $table) {
                $table->dropIndex(['operator_slug']);
            });

            DB::statement('ALTER TABLE tours CHANGE operator_slug admin_slug VARCHAR(255) NULL');

            Schema::table('tours', function (Blueprint $table) {
                $table->index('admin_slug');
            });
        }

        if (Schema::hasColumn('bookings', 'operator_slug') && ! Schema::hasColumn('bookings', 'admin_slug')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropIndex(['operator_slug']);
            });

            DB::statement('ALTER TABLE bookings CHANGE operator_slug admin_slug VARCHAR(255) NULL');

            Schema::table('bookings', function (Blueprint $table) {
                $table->index('admin_slug');
            });
        }

        DB::table('bookings')
            ->where('booked_by_type', 'operator')
            ->update(['booked_by_type' => 'admin']);

        if (Schema::hasTable('otps')) {
            DB::table('otps')
                ->where('guard', 'operator')
                ->update(['guard' => 'admin']);
        }

        Schema::dropIfExists('operators');
    }

    public function down(): void
    {
        if (! Schema::hasTable('operators')) {
            Schema::create('operators', function (Blueprint $table) {
                $table->id();
                $table->string('operator_slug')->unique();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('phone_number')->nullable();
                $table->string('email')->unique();
                $table->string('organization')->nullable();
                $table->string('location')->nullable();
                $table->boolean('is_verified')->default(false);
                $table->timestamp('verified_at')->nullable();
                $table->string('status')->default('pending');
                $table->string('profile_image')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (Schema::hasColumn('tours', 'admin_slug') && ! Schema::hasColumn('tours', 'operator_slug')) {
            Schema::table('tours', function (Blueprint $table) {
                $table->dropIndex(['admin_slug']);
            });

            DB::statement('ALTER TABLE tours CHANGE admin_slug operator_slug VARCHAR(255) NULL');

            Schema::table('tours', function (Blueprint $table) {
                $table->index('operator_slug');
            });
        }

        if (Schema::hasColumn('bookings', 'admin_slug') && ! Schema::hasColumn('bookings', 'operator_slug')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropIndex(['admin_slug']);
            });

            DB::statement('ALTER TABLE bookings CHANGE admin_slug operator_slug VARCHAR(255) NULL');

            Schema::table('bookings', function (Blueprint $table) {
                $table->index('operator_slug');
            });
        }
    }
};
