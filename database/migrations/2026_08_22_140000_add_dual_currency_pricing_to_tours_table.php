<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->decimal('price_amount_ghs', 12, 2)->nullable()->after('price_amount');
            $table->decimal('price_amount_usd', 12, 2)->nullable()->after('price_amount_ghs');
            $table->string('audience_scope', 20)->default('local')->after('price_amount_usd');
        });

        DB::table('tours')->orderBy('id')->chunkById(100, function ($tours) {
            foreach ($tours as $tour) {
                $currency = strtoupper((string) ($tour->price_currency ?? 'USD'));
                $amount = (float) ($tour->price_amount ?? 0);
                $updates = [];

                if ($currency === 'GHS' && $amount > 0) {
                    $updates['price_amount_ghs'] = $amount;
                    $updates['audience_scope'] = 'local';
                } elseif ($currency === 'USD' && $amount > 0) {
                    $updates['price_amount_usd'] = $amount;
                    $updates['audience_scope'] = 'foreign';
                }

                if ($updates !== []) {
                    DB::table('tours')->where('id', $tour->id)->update($updates);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn(['price_amount_ghs', 'price_amount_usd', 'audience_scope']);
        });
    }
};
