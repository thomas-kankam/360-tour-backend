<?php

namespace App\Models;

use App\Services\TourRatingService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rating extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'rating_uuid',
        'tour_slug',
        'client_slug',
        'rating',
        'comment',
        'status',
    ];

    protected $casts = [
        'rating' => 'integer',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'rating_uuid';
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class, 'tour_slug', 'tour_slug');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_slug', 'client_slug');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isLocked(): bool
    {
        return $this->isApproved();
    }

    protected static function booted(): void
    {
        static::deleted(function (Rating $rating): void {
            if ($rating->status === 'approved') {
                app(TourRatingService::class)->syncForTour($rating->tour_slug);
            }
        });
    }

    public function toRatingArray(bool $includeClientEmail = false): array
    {
        $data = [
            'id' => $this->rating_uuid,
            'tour_slug' => $this->tour_slug,
            'tour_title' => $this->relationLoaded('tour') && $this->tour ? $this->tour->name : null,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];

        if ($this->relationLoaded('client') && $this->client) {
            $data['client_name'] = trim($this->client->first_name . ' ' . ($this->client->last_name ?? ''));
            if ($includeClientEmail) {
                $data['client_email'] = $this->client->email;
            }
        }

        if ($this->relationLoaded('tour') && $this->tour && ! $includeClientEmail) {
            $data['tour'] = $this->tour->toListingArray();
        }

        return $data;
    }

    public function toPublicReviewArray(): array
    {
        $authorName = 'Guest';
        if ($this->relationLoaded('client') && $this->client) {
            $authorName = trim($this->client->first_name . ' ' . ($this->client->last_name ?? '')) ?: 'Guest';
        }

        return [
            'id' => $this->rating_uuid,
            'tour_slug' => $this->tour_slug,
            'tour_title' => $this->relationLoaded('tour') && $this->tour ? $this->tour->name : null,
            'author_name' => $authorName,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
