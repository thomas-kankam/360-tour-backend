<?php

namespace App\Models;

use App\Support\GhanaRegions;
use App\Traits\Helpers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tour extends Model
{
    use HasFactory, Helpers, SoftDeletes;

    protected $fillable = [
        'tour_slug',
        'name',
        'locations',
        'country',
        'country_code',
        'categories',
        'tour_type',
        'regions',
        'status',
        'duration_days',
        'duration_label',
        'group_size_min',
        'group_size_max',
        'group_size_label',
        'price_amount',
        'price_amount_ghs',
        'price_amount_usd',
        'audience_scope',
        'price_currency',
        'price_label',
        'cover_image_url',
        'gallery_image_urls',
        'description',
        'highlights',
        'itinerary',
        'included',
        'not_included',
        'departure_dates',
        'booking_settings',
        'created_by_admin_slug',
        'admin_slug',
        'booking_count',
        'rating',
        'review_count',
    ];

    public const TYPE_REGULAR = 'regular';

    public const TYPE_CUSTOM = 'custom';

    public const TYPES = [self::TYPE_REGULAR, self::TYPE_CUSTOM];

    protected $casts = [
        'categories' => 'array',
        'regions' => 'array',
        'gallery_image_urls' => 'array',
        'highlights' => 'array',
        'itinerary' => 'array',
        'included' => 'array',
        'not_included' => 'array',
        'departure_dates' => 'array',
        'booking_settings' => 'array',
        'price_amount' => 'decimal:2',
        'price_amount_ghs' => 'decimal:2',
        'price_amount_usd' => 'decimal:2',
        'locations' => 'array',
        'rating' => 'decimal:1',
        'review_count' => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'tour_slug';
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_slug', 'admin_slug');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'tour_slug', 'tour_slug');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class, 'tour_slug', 'tour_slug');
    }

    public static function syncBookingCountFor(?string $tourSlug): void
    {
        if (! $tourSlug) {
            return;
        }

        static::query()
            ->where('tour_slug', $tourSlug)
            ->update([
                'booking_count' => Booking::query()->where('tour_slug', $tourSlug)->count(),
            ]);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('tour_type', $type);
    }

    /**
     * Filters to tours that visit a Ghana region. Falls back to matching the
     * region label inside `locations` so tours saved before the `regions`
     * column existed still show up.
     */
    public function scopeInRegion(Builder $query, string $regionId): Builder
    {
        $label = GhanaRegions::label($regionId);

        return $query->where(function (Builder $inner) use ($regionId, $label) {
            $inner->whereJsonContains('regions', $regionId);

            if ($label !== '') {
                $inner->orWhere('locations', 'like', '%' . $label . '%');
            }
        });
    }

    /** Match tours with a fixed departure or a date-range window on the given day. */
    public function scopeDepartingOn(Builder $query, string $isoDate): Builder
    {
        return $query->where(function (Builder $inner) use ($isoDate) {
            $inner->where('departure_dates', 'like', '%"date":"' . $isoDate . '"%')
                ->orWhere('departure_dates', 'like', '%"date": "' . $isoDate . '"%')
                ->orWhere('departure_dates', 'like', '%"endDate":"' . $isoDate . '"%')
                ->orWhere('departure_dates', 'like', '%"endDate": "' . $isoDate . '"%');
        });
    }

    public function isCustom(): bool
    {
        return $this->tour_type === self::TYPE_CUSTOM;
    }

    /** Keeps `regions` in step with whatever locations were saved. */
    public function syncRegionsFromLocations(): void
    {
        $regions = GhanaRegions::resolveFromLocations($this->locations ?? []);

        if ($regions !== ($this->regions ?? [])) {
            $this->regions = $regions;
        }
    }

    public function toListingArray(): array
    {
        $this->persistInlineImages();

        $data = [
            'slug' => $this->tour_slug,
            'name' => $this->name,
            'locations' => $this->locations ?? [],
            'country' => $this->country,
            'countryCode' => $this->country_code,
            'categories' => $this->categories ?? [],
            'tourType' => $this->tour_type ?: self::TYPE_REGULAR,
            'regions' => $this->regions ?? GhanaRegions::resolveFromLocations($this->locations ?? []),
            'regionLabels' => array_values(array_filter(array_map(
                fn ($regionId) => GhanaRegions::label($regionId),
                $this->regions ?? GhanaRegions::resolveFromLocations($this->locations ?? [])
            ))),
            'status' => $this->status,
            'durationDays' => $this->duration_days,
            'durationLabel' => $this->duration_label,
            'groupSizeMin' => $this->group_size_min,
            'groupSizeMax' => $this->group_size_max,
            'groupSizeLabel' => $this->group_size_label,
            'priceAmount' => (float) $this->price_amount,
            'priceAmountGhs' => $this->price_amount_ghs !== null ? (float) $this->price_amount_ghs : null,
            'priceAmountUsd' => $this->price_amount_usd !== null ? (float) $this->price_amount_usd : null,
            'audienceScope' => $this->audience_scope ?: 'local',
            'priceCurrency' => $this->price_currency,
            'priceLabel' => $this->price_label,
            'coverImageUrl' => static::normalizePublicUrl($this->cover_image_url),
            'galleryImageUrls' => array_values(array_filter(array_map(
                fn ($url) => static::normalizePublicUrl($url),
                $this->gallery_image_urls ?? []
            ))),
            'description' => $this->description,
            'highlights' => $this->highlights ?? [],
            'itinerary' => static::normalizeItineraryForOutput($this->itinerary ?? []),
            'included' => $this->included ?? [],
            'notIncluded' => $this->not_included ?? [],
            'departureDates' => $this->departure_dates ?? [],
            'bookingSettings' => $this->booking_settings ?? [],
            'adminSlug' => $this->admin_slug,
            'bookingCount' => $this->booking_count,
            'rating' => (float) $this->rating,
            'reviewCount' => (int) $this->review_count,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];

        if ($this->relationLoaded('admin') && $this->admin) {
            $data['admin'] = $this->admin->toAdminArray();
        }

        return $data;
    }

    protected function persistInlineImages(): void
    {
        $dirty = false;

        $cover = static::persistStoredImageValue($this->cover_image_url, 'tour');
        if ($cover !== $this->cover_image_url && ($cover || str_starts_with((string) $this->cover_image_url, 'data:'))) {
            $this->cover_image_url = $cover;
            $dirty = true;
        }

        $gallery = $this->gallery_image_urls ?? [];
        $nextGallery = [];
        $galleryChanged = false;
        foreach ($gallery as $url) {
            $persisted = static::persistStoredImageValue($url, 'tour');
            if ($persisted !== $url) {
                $galleryChanged = true;
            }
            if ($persisted) {
                $nextGallery[] = $persisted;
            } elseif (! str_starts_with((string) $url, 'data:')) {
                $nextGallery[] = $url;
                $galleryChanged = true;
            } else {
                $galleryChanged = true;
            }
        }
        if ($galleryChanged) {
            $this->gallery_image_urls = array_values($nextGallery);
            $dirty = true;
        }

        $itinerary = $this->itinerary ?? [];
        $itineraryChanged = false;
        $nextItinerary = array_map(function ($day) use (&$itineraryChanged) {
            if (! is_array($day)) {
                return $day;
            }

            $imageUrl = $day['imageUrl'] ?? $day['image_url'] ?? null;
            if (! $imageUrl) {
                return $day;
            }

            $persisted = static::persistStoredImageValue($imageUrl, 'tour');
            if ($persisted !== $imageUrl) {
                $itineraryChanged = true;
                $day['imageUrl'] = $persisted;
                unset($day['image_url']);
            }

            return $day;
        }, $itinerary);

        if ($itineraryChanged) {
            $this->itinerary = $nextItinerary;
            $dirty = true;
        }

        if ($dirty) {
            $this->saveQuietly();
        }
    }
}
