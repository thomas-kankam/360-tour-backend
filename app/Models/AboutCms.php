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
                'description' => 'Welcome to 360 Tours and Investment Limited, where unforgettable travel experiences begin. We create exciting, safe, and seamless journeys across Ghana and beyond, with guided tours, comfortable stays, and reliable transport under one roof.',
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
                'intro' => 'Welcome to 360 Tours and Investment Limited, your trusted travel partner for unforgettable experiences across Ghana and beyond.',
                'story' => '360 Tours and Investment Limited is a registered travel and tourism company dedicated to showcasing the very best of Ghana and Africa.',
                'journey' => 'From Accra to Cape Coast, Akosombo, Kakum, and the Volta Region, we help guests discover Africa from a local perspective.',
                'commitment' => 'Our goal is to provide personalized travel experiences that leave lasting memories while supporting sustainable tourism and local communities.',
            ],
            'mission' => [
                'title' => 'Our Mission',
                'text' => 'To deliver exceptional travel experiences through quality service, professionalism, and authentic cultural connections while promoting sustainable tourism throughout Ghana and Africa.',
            ],
            'vision' => [
                'title' => 'Our Vision',
                'text' => 'To become Africa\'s leading travel and tourism company by inspiring travelers to explore the continent through unforgettable experiences, outstanding service, and innovative travel solutions.',
            ],
            'values' => [
                'Excellence',
                'Integrity',
                'Customer Satisfaction',
                'Safety',
                'Professionalism',
                'Sustainability',
                'Innovation',
                'Reliability',
            ],
            'tourServices' => [],
            'supportServices' => [],
            'popularDestinations' => [],
            'whyTravelWithUs' => [],
            'faqs' => [],
            'cta' => [
                'title' => 'Your Adventure Begins Here',
                'subtitle' => 'Ready to discover the beauty, history, and culture of Ghana? Book your next unforgettable journey with 360 Tours and Investment Limited.',
                'primaryLabel' => 'Contact us',
                'primaryTo' => '/contact',
                'secondaryLabel' => 'Browse tours',
                'secondaryTo' => '/tours',
            ],
            'teaser' => [
                'eyebrow' => 'About Us',
                'title' => 'Who We Are',
                'tagline' => 'Discover Africa. Travel Without Limits.',
                'subtitle' => 'Your trusted travel partner for tours, accommodation & transportation',
                'summary' => '360 Tours and Investment Limited is a registered travel and tourism company dedicated to showcasing the very best of Ghana and Africa.',
                'extended' => 'From Accra to Cape Coast, Akosombo to the Volta Region, we help travelers discover Africa from a local perspective.',
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
            $sectionContent = $content[$section] ?? null;

            if (is_array($fields) && array_is_list($fields)) {
                // Empty list from draft means "unset" — keep the seeded About page defaults.
                $merged[$section] = (is_array($sectionContent) && count($sectionContent) > 0)
                    ? $sectionContent
                    : $fields;
                continue;
            }

            if (is_array($fields)) {
                $mergedSection = array_merge($fields, is_array($sectionContent) ? $sectionContent : []);
                foreach ($fields as $key => $defaultValue) {
                    if (is_string($defaultValue)
                        && array_key_exists($key, $mergedSection)
                        && is_string($mergedSection[$key])
                        && trim($mergedSection[$key]) === ''
                        && trim((string) $defaultValue) !== ''
                    ) {
                        $mergedSection[$key] = $defaultValue;
                    }
                }
                $merged[$section] = $mergedSection;
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
