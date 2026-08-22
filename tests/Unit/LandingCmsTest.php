<?php

namespace Tests\Unit;

use App\Models\LandingCms;
use Tests\TestCase;

class LandingCmsTest extends TestCase
{
    public function test_defaults_include_editable_destination_items_with_local_photos(): void
    {
        $items = LandingCms::defaultDestinationItems();

        $this->assertCount(15, $items);
        $this->assertSame('Accra City Tour', $items[0]['name']);
        $this->assertSame('/images/home/arts_and_craft.jpg', $items[0]['image']);
        $this->assertStringStartsWith('/images/home/', $items[1]['image']);
    }

    public function test_merge_keeps_default_items_when_cms_payload_omits_them(): void
    {
        $merged = (new LandingCms())->mergeWithDefaults([
            'destinations' => ['title' => 'Popular Destinations'],
        ]);

        $this->assertNotEmpty($merged['destinations']['items']);
        $this->assertSame('Cape Coast Castle', $merged['destinations']['items'][1]['name']);
    }

    public function test_merge_preserves_admin_uploaded_item_images(): void
    {
        $merged = (new LandingCms())->mergeWithDefaults([
            'destinations' => [
                'items' => [
                    [
                        'id' => 'accra-city-tour',
                        'image' => 'http://127.0.0.1:8000/storage/uploads/images/accra.webp',
                    ],
                ],
            ],
        ]);

        $this->assertSame(
            'http://127.0.0.1:8000/storage/uploads/images/accra.webp',
            $merged['destinations']['items'][0]['image']
        );
    }
}
