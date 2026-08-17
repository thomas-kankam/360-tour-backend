<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingCms extends Model
{
    protected $table = 'landing_cms';

    protected $fillable = [
        'draft_content',
        'published_content',
        'draft_updated_at',
        'published_at',
        'published_by',
    ];

    protected $casts = [
        'draft_content' => 'array',
        'published_content' => 'array',
        'draft_updated_at' => 'datetime',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function defaultContent(): array
    {
        return [
            'hero' => [
                'badge' => '360 Tours and Investment Limited',
                'title' => 'Discover Africa.',
                'titleHighlight' => 'Travel Without Limits.',
                'subtitle' => 'Tours, stays, and transport across Ghana and beyond, planned for you.',
                'tagline' => 'Explore More. Travel Better. Experience Africa with 360 Tours.',
                'primaryCtaLabel' => 'Explore our tours',
                'secondaryCtaLabel' => 'Plan your trip',
                'backgroundImage' => '/images/gallery/optimized/hero.webp',
            ],
            'tours' => [
                'eyebrow' => 'Featured tours',
                'title' => 'Top picks for your next trip',
                'subtitle' => 'Heritage tours, adventure trails, beach getaways, and city escapes curated for every traveler.',
                'viewAllLabel' => 'View all tours',
            ],
            'destinations' => [
                'eyebrow' => 'Popular destinations',
                'title' => 'Where travelers go next',
                'subtitle' => 'Swipe through Ghana highlights and find your next adventure.',
                'ctaLabel' => 'View all tours',
                'bookLabel' => 'Book this experience',
            ],
            'regions' => [
                'eyebrow' => 'Where We Operate',
                'title' => 'Discover Ghana, Region by Region',
                'subtitle' => 'From Accra\'s vibrant streets to Cape Coast\'s historic castles, explore Ghana one region at a time.',
                'ctaLabel' => 'View all Ghana tours',
                'footerNote' => 'Ghana is our home base, with curated experiences across Africa.',
            ],
            'explore' => [
                'eyebrow' => 'Learn more',
                'title' => 'Explore 360 Tours',
                'aboutLabel' => 'About us',
                'aboutText' => 'Our story, services, and offices in Ghana and Amsterdam.',
                'aboutCta' => 'About 360 Tours',
                'whyLabel' => 'Why choose us',
                'whyText' => 'Guided tours, flexible departures, and end-to-end coordination.',
                'whyCta' => 'See why travelers trust us',
                'contactLabel' => 'Plan your trip',
                'contactText' => 'Custom quotes, group travel, and visa on arrival guidance.',
                'contactCta' => 'Contact us',
            ],
            'cta' => [
                'eyebrow' => 'Ready to travel?',
                'title' => 'Plan your Ghana adventure',
                'subtitle' => 'Tell us your dates and interests, we will handle the rest.',
                'primaryCtaLabel' => 'Contact us',
                'secondaryCtaLabel' => 'View all tours',
                'whatsappMessage' => 'Hi, I\'d like to plan a trip with 360 Tours.',
                'image' => '/images/home/hero_two.jpg',
            ],
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }

    public function editorDraft(): array
    {
        if ($this->draft_content !== null) {
            return $this->mergeWithDefaults($this->draft_content);
        }

        if ($this->published_content !== null) {
            return $this->mergeWithDefaults($this->published_content);
        }

        return static::defaultContent();
    }

    public function mergeWithDefaults(array $content): array
    {
        $defaults = static::defaultContent();
        $merged = [];

        foreach ($defaults as $section => $fields) {
            $merged[$section] = array_merge($fields, $content[$section] ?? []);
        }

        return $merged;
    }

    public function hasUnpublishedChanges(): bool
    {
        if ($this->draft_content === null) {
            return false;
        }

        if ($this->published_content === null) {
            return true;
        }

        return $this->normalizeForCompare($this->draft_content) !== $this->normalizeForCompare($this->published_content);
    }

    public function meta(): array
    {
        return [
            'draft_updated_at' => $this->draft_updated_at?->toIso8601String(),
            'published_at' => $this->published_at?->toIso8601String(),
            'published_by' => $this->published_by,
            'has_unpublished_changes' => $this->hasUnpublishedChanges(),
        ];
    }

    protected function normalizeForCompare(array $content): string
    {
        return json_encode($this->mergeWithDefaults($content), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
