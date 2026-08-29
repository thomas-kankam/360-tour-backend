<?php

namespace Tests\Feature;

use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ListingSearchTest extends TestCase
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

    public function test_listings_can_be_filtered_by_search_term(): void
    {
        $this->makeTour([
            'name' => 'Volta Waterfall Adventure',
            'locations' => ['Ho, Volta'],
        ]);

        $this->makeTour([
            'name' => 'Accra City Lights',
            'locations' => ['Accra'],
        ]);

        $response = $this->postJson('/api/listings', ['search' => 'Volta']);

        $response->assertOk();
        $names = collect($response->json('data.data.data'))->pluck('name');

        $this->assertTrue($names->contains('Volta Waterfall Adventure'));
        $this->assertFalse($names->contains('Accra City Lights'));
    }
}
