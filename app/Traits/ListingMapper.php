<?php

namespace App\Traits;

use App\Models\Tour;
use App\Support\GhanaRegions;
use Illuminate\Support\Str;

trait ListingMapper
{
    use Helpers;

    /** Anything we do not recognise falls back to a regular scheduled tour. */
    protected static function normalizeTourType(?string $type): string
    {
        $normalized = strtolower(trim((string) $type));

        return in_array($normalized, Tour::TYPES, true) ? $normalized : Tour::TYPE_REGULAR;
    }

    protected static function mapListingPayloadToAttributes(array $data, ?string $adminSlug = null): array
    {
        $status = $data['status'] ?? 'draft';
        $cover = $data['coverImageUrl'] ?? $data['cover_image_url'] ?? null;
        $cover = static::decodeImageUrl($cover, 'tour');

        $gallery = $data['galleryImageUrls'] ?? $data['gallery_image_urls'] ?? [];
        $gallery = array_values(array_filter(array_map(
            fn ($url) => static::decodeImageUrl($url, 'tour'),
            $gallery
        )));

        $itinerary = static::mapItineraryImages($data['itinerary'] ?? []);

        $locations = $data['locations'] ?? [];
        $tourType = static::normalizeTourType($data['tourType'] ?? $data['tour_type'] ?? null);

        $pricing = static::resolveListingPricing($data);

        $attrs = array_filter([
            'tour_slug' => $data['slug'] ?? $data['tour_slug'] ?? (string) Str::uuid(),
            'name' => $data['name'],
            'locations' => $locations,
            'country' => $data['country'] ?? null,
            'country_code' => $data['countryCode'] ?? $data['country_code'] ?? null,
            'categories' => $data['categories'] ?? [],
            'tour_type' => $tourType,
            'regions' => GhanaRegions::resolveFromLocations(is_array($locations) ? $locations : []),
            'status' => $status,
            'duration_days' => $data['durationDays'] ?? $data['duration_days'] ?? null,
            'duration_label' => $data['durationLabel'] ?? $data['duration_label'] ?? null,
            'group_size_min' => $data['groupSizeMin'] ?? $data['group_size_min'] ?? null,
            'group_size_max' => $data['groupSizeMax'] ?? $data['group_size_max'] ?? null,
            'group_size_label' => $data['groupSizeLabel'] ?? $data['group_size_label'] ?? null,
            'price_amount' => $pricing['price_amount'],
            'price_amount_ghs' => $pricing['price_amount_ghs'],
            'price_amount_usd' => $pricing['price_amount_usd'],
            'audience_scope' => $pricing['audience_scope'],
            'price_currency' => $pricing['price_currency'],
            'price_label' => $data['priceLabel'] ?? $data['price_label'] ?? null,
            'cover_image_url' => $cover,
            'gallery_image_urls' => $gallery,
            'description' => $data['description'] ?? null,
            'highlights' => $data['highlights'] ?? [],
            'itinerary' => $itinerary,
            'included' => $data['included'] ?? [],
            'not_included' => $data['notIncluded'] ?? $data['not_included'] ?? [],
            'departure_dates' => $data['departureDates'] ?? $data['departure_dates'] ?? [],
            'booking_settings' => $data['bookingSettings'] ?? $data['booking_settings'] ?? [],
            'created_by_admin_slug' => $adminSlug,
            'admin_slug' => $adminSlug,
        ], fn ($v) => $v !== null);

        if (array_key_exists('bookingCount', $data) || array_key_exists('booking_count', $data)) {
            $attrs['booking_count'] = (int) ($data['bookingCount'] ?? $data['booking_count'] ?? 0);
        }

        return $attrs;
    }

    protected static function calculateBookingAmount(Tour $tour, int $travelers, ?string $currency = null): float
    {
        $unitPrice = static::resolveTourUnitPrice($tour, $currency);
        $base = round($unitPrice * $travelers, 2);
        $settings = $tour->booking_settings ?? [];
        $depositPercent = (int) ($settings['depositPercent'] ?? 100);

        return round($base * ($depositPercent / 100), 2);
    }

    protected static function resolveTourUnitPrice(Tour $tour, ?string $currency = null): float
    {
        $currency = strtoupper(trim((string) ($currency ?: $tour->price_currency ?: 'GHS')));

        if ($currency === 'USD') {
            $usd = (float) ($tour->price_amount_usd ?? 0);

            return $usd > 0 ? $usd : (float) $tour->price_amount;
        }

        $ghs = (float) ($tour->price_amount_ghs ?? 0);

        return $ghs > 0 ? $ghs : (float) $tour->price_amount;
    }

    protected static function resolveListingPricing(array $data): array
    {
        $audienceScope = strtolower(trim((string) ($data['audienceScope'] ?? $data['audience_scope'] ?? '')));
        $priceAmountGhs = static::parseListingPrice($data['priceAmountGhs'] ?? $data['price_amount_ghs'] ?? null);
        $priceAmountUsd = static::parseListingPrice($data['priceAmountUsd'] ?? $data['price_amount_usd'] ?? null);
        $legacyAmount = static::parseListingPrice($data['priceAmount'] ?? $data['price_amount'] ?? null);
        $legacyCurrency = strtoupper(trim((string) ($data['priceCurrency'] ?? $data['price_currency'] ?? '')));

        if ($priceAmountGhs === null && $legacyCurrency === 'GHS' && $legacyAmount !== null) {
            $priceAmountGhs = $legacyAmount;
        }

        if ($priceAmountUsd === null && $legacyCurrency === 'USD' && $legacyAmount !== null) {
            $priceAmountUsd = $legacyAmount;
        }

        if ($priceAmountGhs === null && $priceAmountUsd === null && $legacyAmount !== null) {
            if ($legacyCurrency === 'USD') {
                $priceAmountUsd = $legacyAmount;
            } else {
                $priceAmountGhs = $legacyAmount;
            }
        }

        if (! in_array($audienceScope, ['local', 'foreign', 'global'], true)) {
            $hasGhs = ($priceAmountGhs ?? 0) > 0;
            $hasUsd = ($priceAmountUsd ?? 0) > 0;

            if ($hasGhs && $hasUsd) {
                $audienceScope = 'global';
            } elseif ($hasUsd && ! $hasGhs) {
                $audienceScope = 'foreign';
            } else {
                $audienceScope = 'local';
            }
        }

        if ($audienceScope === 'foreign') {
            $priceAmount = $priceAmountUsd ?? 0;
            $priceCurrency = 'USD';
        } elseif ($audienceScope === 'global') {
            $priceAmount = $priceAmountGhs ?? 0;
            $priceCurrency = 'GHS';
        } else {
            $priceAmount = $priceAmountGhs ?? 0;
            $priceCurrency = 'GHS';
        }

        return [
            'price_amount' => $priceAmount,
            'price_amount_ghs' => $priceAmountGhs,
            'price_amount_usd' => $priceAmountUsd,
            'audience_scope' => $audienceScope,
            'price_currency' => $priceCurrency,
        ];
    }

    protected static function parseListingPrice(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $amount = (float) $value;

        return $amount >= 0 ? $amount : null;
    }

    protected static function mapItineraryImages(array $itinerary): array
    {
        return array_values(array_map(function ($day) {
            if (! is_array($day)) {
                return $day;
            }

            $imageUrl = $day['imageUrl'] ?? $day['image_url'] ?? null;
            if ($imageUrl) {
                $day['imageUrl'] = static::decodeImageUrl($imageUrl, 'tour');
                unset($day['image_url']);
            }

            if (isset($day['accommodation']) && is_array($day['accommodation'])) {
                $accImage = $day['accommodation']['imageUrl'] ?? $day['accommodation']['image_url'] ?? null;
                if ($accImage) {
                    $day['accommodation']['imageUrl'] = static::decodeImageUrl($accImage, 'tour');
                    unset($day['accommodation']['image_url']);
                }
            }

            if (isset($day['meals']) && is_array($day['meals'])) {
                foreach ($day['meals'] as $mealIndex => $meal) {
                    if (! is_array($meal)) {
                        continue;
                    }
                    $mealImage = $meal['imageUrl'] ?? $meal['image_url'] ?? null;
                    if ($mealImage) {
                        $day['meals'][$mealIndex]['imageUrl'] = static::decodeImageUrl($mealImage, 'tour');
                        unset($day['meals'][$mealIndex]['image_url']);
                    }
                }
            }

            return $day;
        }, $itinerary));
    }
}
