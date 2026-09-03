<?php

namespace App\Models;

use App\Traits\Helpers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Experience extends Model
{
    use Helpers;

    protected $fillable = [
        'experience_key',
        'slug',
        'label',
        'icon_key',
        'tagline',
        'description',
        'highlights',
        'regions',
        'keywords',
        'image',
        'badge_text',
        'tour_query',
        'story_category',
        'related_story_slugs',
        'status',
        'published_at',
        'sort_order',
    ];

    protected $casts = [
        'highlights' => 'array',
        'regions' => 'array',
        'keywords' => 'array',
        'tour_query' => 'array',
        'related_story_slugs' => 'array',
        'published_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function toExperienceArray(bool $includeStatus = false): array
    {
        $image = static::normalizePublicUrl($this->image) ?? $this->image;

        $payload = [
            'id' => $this->id,
            'key' => $this->experience_key,
            'slug' => $this->slug,
            'label' => $this->label,
            'iconKey' => $this->icon_key,
            'tagline' => $this->tagline,
            'description' => $this->description,
            'highlights' => is_array($this->highlights) ? $this->highlights : [],
            'regions' => is_array($this->regions) ? $this->regions : [],
            'keywords' => is_array($this->keywords) ? $this->keywords : [],
            'image' => $image,
            'badgeText' => $this->badge_text,
            'tourQuery' => is_array($this->tour_query) ? $this->tour_query : [],
            'storyCategory' => $this->story_category,
            'relatedStorySlugs' => is_array($this->related_story_slugs) ? $this->related_story_slugs : [],
            'sortOrder' => (int) $this->sort_order,
            'publishedAt' => $this->published_at?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];

        if ($includeStatus) {
            $payload['status'] = $this->status;
        }

        return $payload;
    }

    public function persistImagePath(): void
    {
        if (! $this->image) {
            return;
        }

        $persisted = static::persistCmsImageValue($this->image, 'destination');
        if ($persisted && $persisted !== $this->image) {
            $this->forceFill(['image' => $persisted])->saveQuietly();
        }
    }
}
