<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class AboutCms extends Model
{
    protected $table = 'about_cms';

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
        $path = database_path('data/about_cms_defaults.json');
        if (File::exists($path)) {
            $decoded = json_decode(File::get($path), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [
            'hero' => [
                'eyebrow' => 'About Us',
                'title' => 'Who We Are',
                'titleLine' => 'Discover Africa.',
                'titleHighlight' => 'Travel Without Limits.',
                'description' => 'Welcome to 360 Tours and Investment Limited, where unforgettable travel experiences begin.',
                'tagline' => 'Explore More. Travel Better. Experience Africa with 360 Tours.',
                'services' => [
                    ['label' => 'Guided Tours', 'icon' => 'compass'],
                    ['label' => 'Accommodation', 'icon' => 'building'],
                    ['label' => 'Transportation', 'icon' => 'car'],
                ],
                'heroImage' => '/images/gallery/optimized/hero.webp',
                'storyImage' => '/images/home/manhyia_palace.jpg',
            ],
            'company' => [
                'name' => '360 Tours and Investment Limited',
                'shortName' => '360 Tours Ghana',
                'tagline' => 'Discover Africa. Travel Without Limits.',
                'subtitle' => 'Your trusted travel partner for tours, accommodation & transportation',
                'location' => 'Accra, Ghana',
                'motto' => 'Explore. Experience. Remember.',
            ],
            'story' => [
                'intro' => '',
                'story' => '',
                'journey' => '',
                'commitment' => '',
            ],
            'mission' => ['title' => 'Our Mission', 'text' => ''],
            'vision' => ['title' => 'Our Vision', 'text' => ''],
            'values' => [],
            'tourServices' => [],
            'supportServices' => [],
            'popularDestinations' => [],
            'whyTravelWithUs' => [],
            'faqs' => [],
            'cta' => [
                'title' => 'Your Adventure Begins Here',
                'subtitle' => '',
                'primaryLabel' => 'Contact us',
                'primaryTo' => '/contact',
                'secondaryLabel' => 'Browse tours',
                'secondaryTo' => '/tours',
            ],
            'teaser' => [],
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
            $sectionContent = $content[$section] ?? null;

            if (is_array($fields) && array_is_list($fields)) {
                $merged[$section] = is_array($sectionContent) ? $sectionContent : $fields;
                continue;
            }

            if (is_array($fields)) {
                $merged[$section] = array_merge($fields, is_array($sectionContent) ? $sectionContent : []);
                continue;
            }

            $merged[$section] = $sectionContent ?? $fields;
        }

        foreach ($content as $section => $value) {
            if (! array_key_exists($section, $merged)) {
                $merged[$section] = $value;
            }
        }

        return $merged;
    }

    public function hasUnpublishedChanges(): bool
    {
        return json_encode($this->draft_content) !== json_encode($this->published_content);
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
}
