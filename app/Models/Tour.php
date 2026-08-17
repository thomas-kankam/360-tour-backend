<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tour extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tour_slug',
        'name',
        'locations',
        'country',
        'country_code',
        'categories',
        'status',
        'featured',
        'duration_days',
        'duration_label',
        'group_size_min',
        'group_size_max',
        'group_size_label',
        'price_amount',
        'price_currency',
        'price_label',
        'badge',
        'badge_variant',
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

    protected $casts = [
        'categories' => 'array',
        'featured' => 'boolean',
        'gallery_image_urls' => 'array',
        'highlights' => 'array',
        'itinerary' => 'array',
        'included' => 'array',
        'not_included' => 'array',
        'departure_dates' => 'array',
        'booking_settings' => 'array',
        'price_amount' => 'decimal:2',
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

    public function toListingArray(): array
    {
        $data = [
            'slug' => $this->tour_slug,
            'name' => $this->name,
            'locations' => $this->locations ?? [],
            'country' => $this->country,
            'countryCode' => $this->country_code,
            'categories' => $this->categories ?? [],
            'status' => $this->status,
            'featured' => $this->featured,
            'durationDays' => $this->duration_days,
            'durationLabel' => $this->duration_label,
            'groupSizeMin' => $this->group_size_min,
            'groupSizeMax' => $this->group_size_max,
            'groupSizeLabel' => $this->group_size_label,
            'priceAmount' => (float) $this->price_amount,
            'priceCurrency' => $this->price_currency,
            'priceLabel' => $this->price_label,
            'badge' => $this->badge,
            'badgeVariant' => $this->badge_variant,
            'coverImageUrl' => $this->cover_image_url,
            'galleryImageUrls' => $this->gallery_image_urls ?? [],
            'description' => $this->description,
            'highlights' => $this->highlights ?? [],
            'itinerary' => $this->itinerary ?? [],
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
}
