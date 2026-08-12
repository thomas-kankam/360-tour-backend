<?php

namespace App\Traits;

use App\Models\Tour;
use Illuminate\Support\Str;

trait ListingMapper
{
    use Helpers;

    protected static function mapListingPayloadToAttributes(array $data, ?string $adminSlug = null): array
    {
        $status = $data['status'] ?? 'draft';
        $cover = $data['coverImageUrl'] ?? $data['cover_image_url'] ?? null;
        if ($cover && str_starts_with($cover, 'data:')) {
            $cover = static::base64ImageDecode($cover);
        }

        $gallery = $data['galleryImageUrls'] ?? $data['gallery_image_urls'] ?? [];
        $gallery = array_values(array_filter(array_map(function ($url) {
            if ($url && str_starts_with($url, 'data:')) {
                return static::base64ImageDecode($url);
            }

            return $url;
        }, $gallery)));

        return array_filter([
            'tour_slug' => $data['slug'] ?? $data['tour_slug'] ?? (string) Str::uuid(),
            'name' => $data['name'],
            'locations' => $data['locations'] ?? $data['locations'] ?? [],
            'country' => $data['country'] ?? null,
            'country_code' => $data['countryCode'] ?? $data['country_code'] ?? null,
            'categories' => $data['categories'] ?? [],
            'status' => $status,
            'featured' => (bool) ($data['featured'] ?? false),
            'duration_days' => $data['durationDays'] ?? $data['duration_days'] ?? null,
            'duration_label' => $data['durationLabel'] ?? $data['duration_label'] ?? null,
            'group_size_min' => $data['groupSizeMin'] ?? $data['group_size_min'] ?? null,
            'group_size_max' => $data['groupSizeMax'] ?? $data['group_size_max'] ?? null,
            'group_size_label' => $data['groupSizeLabel'] ?? $data['group_size_label'] ?? null,
            'price_amount' => $data['priceAmount'] ?? $data['price_amount'] ?? 0,
            'price_currency' => $data['priceCurrency'] ?? $data['price_currency'] ?? 'USD',
            'price_label' => $data['priceLabel'] ?? $data['price_label'] ?? null,
            'badge' => $data['badge'] ?? null,
            'badge_variant' => $data['badgeVariant'] ?? $data['badge_variant'] ?? null,
            'cover_image_url' => $cover,
            'gallery_image_urls' => $gallery,
            'description' => $data['description'] ?? null,
            'highlights' => $data['highlights'] ?? [],
            'itinerary' => $data['itinerary'] ?? [],
            'included' => $data['included'] ?? [],
            'not_included' => $data['notIncluded'] ?? $data['not_included'] ?? [],
            'departure_dates' => $data['departureDates'] ?? $data['departure_dates'] ?? [],
            'booking_settings' => $data['bookingSettings'] ?? $data['booking_settings'] ?? [],
            'booking_count' => $data['bookingCount'] ?? $data['booking_count'] ?? 0,
            'created_by_admin_slug' => $adminSlug,
            'admin_slug' => $adminSlug,
        ], fn($v) => $v !== null);
    }

    protected static function calculateBookingAmount(Tour $tour, int $travelers): float
    {
        $base = (float) $tour->price_amount * $travelers;
        $settings = $tour->booking_settings ?? [];
        $depositPercent = (int) ($settings['depositPercent'] ?? 100);

        return round($base * ($depositPercent / 100), 2);
    }
}
