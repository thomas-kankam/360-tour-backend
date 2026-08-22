<?php

namespace Tests\Feature;

use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ListingRegionFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function makeTour(array $attributes = []): Tour
    {
        return Tour::create(array_merge([
            'tour_slug' => (string) Str::uuid(),
            'name' => 'Test tour',
            'country' => 'Ghana',
            'status' => 'published',
            'tour_type' => Tour::TYPE_REGULAR,
            'price_amount' => 1200,
            'price_currency' => 'GHS',
            'duration_days' => 3,
        ], $attributes));
    }

    public function test_regions_endpoint_only_lists_regions_with_published_tours(): void
    {
        $this->makeTour([
            'locations' => ['Cape Coast, Central', 'Elmina, Central'],
            'regions' => ['central'],
        ]);
        $this->makeTour([
            'locations' => ['Ho, Volta'],
            'regions' => ['volta'],
        ]);
        $this->makeTour([
            'status' => 'draft',
            'locations' => ['Kumasi, Ashanti'],
            'regions' => ['ashanti'],
        ]);

        $response = $this->getJson('/api/listings/regions')->assertOk();

        $regions = collect($response->json('data.data.regions'));

        $this->assertSame(2, $response->json('data.data.total'));
        $this->assertEqualsCanonicalizing(['central', 'volta'], $regions->pluck('id')->all());
        $this->assertSame(1, $regions->firstWhere('id', 'central')['count']);
        $this->assertNotContains('ashanti', $regions->pluck('id')->all());
    }

    public function test_index_filters_published_tours_by_region(): void
    {
        $central = $this->makeTour([
            'name' => 'Coastal heritage',
            'locations' => ['Cape Coast, Central'],
            'regions' => ['central'],
        ]);
        $this->makeTour([
            'name' => 'Volta falls',
            'locations' => ['Wli, Volta'],
            'regions' => ['volta'],
        ]);

        $response = $this->postJson('/api/listings', ['region' => 'central'])->assertOk();

        $slugs = collect($response->json('data.data.data'))->pluck('slug')->all();

        $this->assertSame([$central->tour_slug], $slugs);
    }

    public function test_index_matches_legacy_tours_without_a_regions_column_value(): void
    {
        $legacy = $this->makeTour([
            'name' => 'Legacy Kumasi run',
            'locations' => ['Kumasi, Ashanti'],
            'regions' => null,
        ]);

        $response = $this->postJson('/api/listings', ['region' => 'ashanti'])->assertOk();

        $this->assertSame([$legacy->tour_slug], collect($response->json('data.data.data'))->pluck('slug')->all());
    }

    public function test_index_filters_by_tour_type(): void
    {
        $this->makeTour(['name' => 'Scheduled departure']);
        $custom = $this->makeTour([
            'name' => 'Build your own',
            'tour_type' => Tour::TYPE_CUSTOM,
        ]);

        $response = $this->postJson('/api/listings', ['tour_type' => 'custom'])->assertOk();

        $this->assertSame([$custom->tour_slug], collect($response->json('data.data.data'))->pluck('slug')->all());
    }

    public function test_listing_payload_exposes_tour_type_and_region_labels(): void
    {
        $tour = $this->makeTour([
            'locations' => ['Cape Coast, Central', 'Ho, Volta'],
            'regions' => ['central', 'volta'],
            'tour_type' => Tour::TYPE_CUSTOM,
        ]);

        $payload = $tour->toListingArray();

        $this->assertSame('custom', $payload['tourType']);
        $this->assertSame(['central', 'volta'], $payload['regions']);
        $this->assertSame(['Central', 'Volta'], $payload['regionLabels']);
        $this->assertArrayNotHasKey('badge', $payload);
        $this->assertArrayNotHasKey('featured', $payload);
    }
}
