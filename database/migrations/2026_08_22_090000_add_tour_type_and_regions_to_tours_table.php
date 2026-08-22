<?php

use App\Models\Tour;
use App\Support\GhanaRegions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            // 'regular' = fixed scheduled departures, 'custom' = tailor-made, enquiry-led.
            $table->string('tour_type')->default('regular')->after('categories');
            // Denormalised Ghana region ids derived from `locations`, for region browsing.
            $table->json('regions')->nullable()->after('tour_type');

            $table->index('tour_type');
        });

        $this->backfillRegions();
    }

    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropIndex(['tour_type']);
            $table->dropColumn(['tour_type', 'regions']);
        });
    }

    /** Populates `regions` for tours that already exist. */
    protected function backfillRegions(): void
    {
        Tour::query()
            ->withTrashed()
            ->select(['id', 'locations'])
            ->chunkById(200, function ($tours) {
                foreach ($tours as $tour) {
                    $regions = GhanaRegions::resolveFromLocations($tour->locations ?? []);

                    Tour::withTrashed()
                        ->whereKey($tour->getKey())
                        ->update(['regions' => json_encode($regions)]);
                }
            });
    }
};
