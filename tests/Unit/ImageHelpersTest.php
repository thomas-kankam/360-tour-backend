<?php

namespace Tests\Unit;

use App\Traits\Helpers;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HelpersProbe
{
    use Helpers {
        decodeImageUrl as public;
        persistStoredImageValue as public;
        persistLandingCmsImages as public;
        extractImageCandidate as public;
        normalizePublicUrl as public;
        storeUploadedImage as public;
        imageVariantSpec as public;
    }
}

class ImageHelpersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_data_uri_is_stored_as_a_file_url_not_base64(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true);
        $dataUri = 'data:image/png;base64,' . base64_encode($png);

        $url = HelpersProbe::persistStoredImageValue($dataUri, 'profile');

        $this->assertIsString($url);
        $this->assertStringNotContainsString('base64,', $url);
        $this->assertStringContainsString('/storage/uploads/images/', $url);
        $this->assertNotEmpty(Storage::disk('public')->allFiles('uploads/images'));
    }

    public function test_uploaded_file_is_optimized_and_stored(): void
    {
        $file = UploadedFile::fake()->image('cover.jpg', 2000, 1400);

        $url = HelpersProbe::storeUploadedImage($file, 'tour');

        $this->assertIsString($url);
        $this->assertStringContainsString('/storage/uploads/images/', $url);
        $this->assertNotEmpty(Storage::disk('public')->allFiles('uploads/images'));
    }

    public function test_landing_cms_nested_item_images_are_persisted(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true);
        $dataUri = 'data:image/png;base64,' . base64_encode($png);

        $content = HelpersProbe::persistLandingCmsImages([
            'destinations' => [
                'items' => [
                    ['id' => 'accra-city-tour', 'image' => $dataUri],
                ],
            ],
        ]);

        $this->assertStringNotContainsString('base64,', $content['destinations']['items'][0]['image']);
        $this->assertStringContainsString('/storage/uploads/images/', $content['destinations']['items'][0]['image']);
    }

    public function test_extracts_image_from_legacy_json_object(): void
    {
        $this->assertSame(
            'https://cdn.example.com/a.jpg',
            HelpersProbe::extractImageCandidate(json_encode(['uri' => 'https://cdn.example.com/a.jpg']))
        );
    }

    public function test_destination_variant_matches_landing_card_ratio(): void
    {
        $spec = HelpersProbe::imageVariantSpec('destination');

        $this->assertSame(1280, $spec['width']);
        $this->assertSame(800, $spec['height']);
        $this->assertSame('cover', $spec['fit']);
    }

    public function test_loopback_storage_urls_are_rewritten_to_configured_app_url(): void
    {
        config([
            'app.url' => 'https://api.360toursghana.com',
            'custom.urls.backend_url' => 'https://api.360toursghana.com',
        ]);

        $this->app->instance('request', Request::create('http://127.0.0.1:8000/api/listings', 'GET'));

        $this->assertSame(
            'https://api.360toursghana.com/storage/uploads/images/a.webp',
            HelpersProbe::normalizePublicUrl('http://127.0.0.1:8000/storage/uploads/images/a.webp')
        );
    }

    public function test_uses_request_host_when_app_url_is_loopback(): void
    {
        config([
            'app.url' => 'http://127.0.0.1:8000',
            'custom.urls.backend_url' => 'http://127.0.0.1:8000',
        ]);

        $this->app->instance('request', Request::create('https://api.360toursghana.com/api/listings', 'GET'));

        $this->assertSame(
            'https://api.360toursghana.com/storage/uploads/images/a.webp',
            HelpersProbe::normalizePublicUrl('/storage/uploads/images/a.webp')
        );
    }

    public function test_persists_storage_urls_as_hostless_paths(): void
    {
        $this->assertSame(
            '/storage/uploads/images/a.webp',
            HelpersProbe::persistStoredImageValue('http://127.0.0.1:8000/storage/uploads/images/a.webp')
        );
    }
}
