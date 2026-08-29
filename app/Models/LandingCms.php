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
                'badge' => '360 Tours Ghana',
                'title' => 'Experience Ghana in',
                'titleHighlight' => '360°',
                'subtitle' => 'Cultural heritage, pristine nature, and unforgettable adventures — tours, stays, and transport across Ghana.',
                'tagline' => 'Explore More. Travel Better. Experience Ghana with 360 Tours.',
                'primaryCtaLabel' => 'Book a tour',
                'secondaryCtaLabel' => 'Plan your trip',
                'backgroundImage' => '/images/gallery/optimized/hero.webp',
            ],
            'tours' => [
                'eyebrow' => 'Discover tours',
                'title' => 'Popular tours',
                'subtitle' => 'Heritage tours, adventure trails, beach getaways, and city escapes curated for every traveler.',
                'viewAllLabel' => 'View all tours',
            ],
            'destinations' => [
                'eyebrow' => 'Tour Packages',
                'title' => 'Popular Destinations',
                'subtitle' => 'Fifteen unforgettable stops across Ghana, from historic castles to rainforest canopy walks.',
                'ctaLabel' => 'View all tours',
                'bookLabel' => 'Book this experience',
                'items' => static::defaultDestinationItems(),
            ],
            'regions' => [
                'eyebrow' => 'Where We Operate',
                'title' => 'Discover Ghana, Region by Region',
                'subtitle' => 'From Accra\'s vibrant streets to Cape Coast\'s historic castles, Volta\'s waterfalls, and beyond, we bring every corner of Ghana to life.',
                'ctaLabel' => 'View all Ghana tours',
                'footerNote' => 'Ghana is our home base, with curated experiences across Africa.',
                'items' => static::defaultRegionItems(),
            ],
            'gallery' => [
                'eyebrow' => 'Gallery',
                'title' => 'Moments from the road',
                'subtitle' => 'Castles on the coast, waterfalls in the Volta hills, palaces in Kumasi, and savanna at sunrise.',
                'ctaLabel' => 'Browse all tours',
                'items' => static::defaultGalleryItems(),
            ],
            'testimonials' => [
                'eyebrow' => 'Traveler stories',
                'title' => 'What our guests say',
                'subtitle' => 'Universities, families, and groups share what it felt like to explore Ghana with 360 Tours.',
                'rating' => '4.9',
                'reviews' => '120+ reviews',
                'items' => static::defaultTestimonialItems(),
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
                'contactEmail' => '360toursghana@gmail.com',
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

    public static function defaultDestinationItems(): array
    {
        return [
            ['id' => 'accra-city-tour', 'name' => 'Accra City Tour', 'region' => 'Greater Accra', 'image' => '/images/home/arts_and_craft.jpg', 'imageKey' => 'accraCityTour'],
            ['id' => 'cape-coast-castle', 'name' => 'Cape Coast Castle', 'region' => 'Central Region', 'image' => '/images/home/ghana_tour.png', 'imageKey' => 'capeCoastCastle'],
            ['id' => 'elmina-castle', 'name' => 'Elmina Castle', 'region' => 'Central Region', 'image' => '/images/home/hero_three.jpg', 'imageKey' => 'elminaCastle'],
            ['id' => 'kakum-national-park', 'name' => 'Kakum National Park', 'region' => 'Central Region', 'image' => '/images/home/dest-ghana.jpg', 'imageKey' => 'kakumNationalPark'],
            ['id' => 'akosombo-boat-cruise', 'name' => 'Akosombo Boat Cruise', 'region' => 'Eastern Region', 'image' => '/images/home/waterfall.jpg', 'imageKey' => 'akosomboBoatCruise'],
            ['id' => 'aburi-botanical-gardens', 'name' => 'Aburi Botanical Gardens', 'region' => 'Eastern Region', 'image' => '/images/home/hero_one.jpg', 'imageKey' => 'aburiBotanicalGardens'],
            ['id' => 'wli-waterfalls', 'name' => 'Wli Waterfalls', 'region' => 'Volta Region', 'image' => '/images/home/volta.jpg', 'imageKey' => 'wliWaterfalls'],
            ['id' => 'boti-falls', 'name' => 'Boti Falls', 'region' => 'Eastern Region', 'image' => '/images/home/waterfall.jpg', 'imageKey' => 'botiFalls'],
            ['id' => 'shai-hills', 'name' => 'Shai Hills Resource Reserve', 'region' => 'Greater Accra', 'image' => '/images/home/hero_four.png', 'imageKey' => 'shaiHills'],
            ['id' => 'ada-foah', 'name' => 'Ada Foah', 'region' => 'Greater Accra', 'image' => '/images/home/hero_two.jpg', 'imageKey' => 'adaFoah'],
            ['id' => 'nzulezu-stilt-village', 'name' => 'Nzulezu Stilt Village', 'region' => 'Western Region', 'image' => '/images/home/dest-ghana.jpg', 'imageKey' => 'nzulezuStiltVillage'],
            ['id' => 'mole-national-park', 'name' => 'Mole National Park', 'region' => 'Northern Region', 'image' => '/images/home/dest-ghana.jpg', 'imageKey' => 'moleNationalPark'],
            ['id' => 'kumasi-cultural-tour', 'name' => 'Kumasi Cultural Tour', 'region' => 'Ashanti Region', 'image' => '/images/home/manhyia_palace.jpg', 'imageKey' => 'kumasiCulturalTour'],
            ['id' => 'volta-region-adventure', 'name' => 'Volta Region Adventure', 'region' => 'Volta Region', 'image' => '/images/home/volta.jpg', 'imageKey' => 'voltaRegionAdventure'],
            ['id' => 'tafi-atome-monkey-sanctuary', 'name' => 'Tafi Atome Monkey Sanctuary', 'region' => 'Volta Region', 'image' => '/images/home/hero.jpg', 'imageKey' => 'tafiAtomeMonkeySanctuary'],
        ];
    }

    public static function defaultRegionItems(): array
    {
        return [
            ['id' => 'accra', 'name' => 'Accra', 'region' => 'Greater Accra', 'tagline' => 'Capital culture & city life', 'desc' => 'Explore Independence Square, W.E.B. Du Bois Centre, bustling markets, and the creative energy of Ghana\'s capital.', 'highlights' => 'City tours, Arts & crafts, Nightlife', 'image' => '/images/home/arts_and_craft.jpg', 'imageKey' => 'accraCityTour', 'packageId' => 'accra'],
            ['id' => 'cape-coast', 'name' => 'Cape Coast', 'region' => 'Central Region', 'tagline' => 'Heritage & history', 'desc' => 'Walk through Cape Coast Castle, Elmina Castle, and UNESCO World Heritage sites that tell Ghana\'s powerful story.', 'highlights' => 'Slave castles, Museums, Coastal tours', 'image' => '/images/home/ghana_tour.png', 'imageKey' => 'capeCoastCastle', 'packageId' => ''],
            ['id' => 'kumasi', 'name' => 'Kumasi', 'region' => 'Ashanti Region', 'tagline' => 'Royal Ashanti heritage', 'desc' => 'Visit Manhyia Palace, kente weaving villages, and the living traditions of the Ashanti Kingdom.', 'highlights' => 'Palace tours, Kente villages, Cultural immersion', 'image' => '/images/home/manhyia_palace.jpg', 'imageKey' => 'kumasiCulturalTour', 'packageId' => 'kumasi'],
            ['id' => 'volta', 'name' => 'Volta Region', 'region' => 'Eastern Volta', 'tagline' => 'Waterfalls & adventure', 'desc' => 'Trek to Wli Falls, explore Boti Falls, canopy walks, and the lush highlands of eastern Ghana.', 'highlights' => 'Wli Falls, Eco tours, Hiking', 'image' => '/images/home/volta.jpg', 'imageKey' => 'wliWaterfalls', 'packageId' => 'volta'],
            ['id' => 'akosombo', 'name' => 'Akosombo', 'region' => 'Eastern Region', 'tagline' => 'River cruises & scenery', 'desc' => 'Enjoy scenic boat cruises on the Volta River, mountain views, and relaxing resort experiences.', 'highlights' => 'Boat cruises, Lake views, Resort stays', 'image' => '/images/home/waterfall.jpg', 'imageKey' => 'akosomboBoatCruise', 'packageId' => ''],
            ['id' => 'northern', 'name' => 'Northern Ghana', 'region' => 'Savanna Zone', 'tagline' => 'Wildlife & nature', 'desc' => 'Discover Mole National Park, savanna landscapes, and unforgettable wildlife adventures in northern Ghana.', 'highlights' => 'Safari, Wildlife, Nature parks', 'image' => '/images/home/dest-ghana.jpg', 'imageKey' => 'moleNationalPark', 'packageId' => ''],
        ];
    }

    public static function defaultGalleryItems(): array
    {
        return [
            ['id' => 'cape-coast-castle', 'slug' => 'cape-coast-castle', 'caption' => 'Cape Coast Castle', 'region' => 'Central', 'image' => '/images/gallery/optimized/cape-coast-castle.webp'],
            ['id' => 'wli-waterfalls', 'slug' => 'wli-waterfalls', 'caption' => 'Wli Waterfalls', 'region' => 'Volta', 'image' => '/images/gallery/optimized/wli-waterfalls.webp'],
            ['id' => 'kumasi-cultural-tour', 'slug' => 'kumasi-cultural-tour', 'caption' => 'Manhyia & Kejetia, Kumasi', 'region' => 'Ashanti', 'image' => '/images/gallery/optimized/kumasi-cultural-tour.webp'],
            ['id' => 'kakum-national-park', 'slug' => 'kakum-national-park', 'caption' => 'Kakum Canopy Walk', 'region' => 'Central', 'image' => '/images/gallery/optimized/kakum-national-park.webp'],
            ['id' => 'accra-city-tour', 'slug' => 'accra-city-tour', 'caption' => 'Accra City Tour', 'region' => 'Greater Accra', 'image' => '/images/gallery/optimized/accra-city-tour.webp'],
            ['id' => 'nzulezu-stilt-village', 'slug' => 'nzulezu-stilt-village', 'caption' => 'Nzulezu Stilt Village', 'region' => 'Western', 'image' => '/images/gallery/optimized/nzulezu-stilt-village.webp'],
            ['id' => 'mole-national-park', 'slug' => 'mole-national-park', 'caption' => 'Mole Safari', 'region' => 'Savannah', 'image' => '/images/gallery/optimized/mole-national-park.webp'],
            ['id' => 'ada-foah', 'slug' => 'ada-foah', 'caption' => 'Ada Foah Estuary', 'region' => 'Greater Accra', 'image' => '/images/gallery/optimized/ada-foah.webp'],
        ];
    }

    public static function defaultTestimonialItems(): array
    {
        return [
            ['id' => 'heritage-guest', 'quote' => 'Our trip to Ghana exceeded every expectation. The guides were knowledgeable, the itinerary was perfectly paced, and every detail was handled.', 'name' => 'Happy Traveler', 'role' => 'Ghana heritage tour guest', 'rating' => '5.0', 'tour' => 'Ghana Heritage', 'initials' => 'HT', 'imageKey' => 'capeCoastCastle', 'image' => '/images/home/ghana_tour.png'],
            ['id' => 'group-leader', 'quote' => '360 Tours made our university group trip seamless. From airport pickup to the final farewell dinner, everything ran on time.', 'name' => 'University Group Leader', 'role' => 'Educational tour organizer', 'rating' => '5.0', 'tour' => 'Group Heritage Tour', 'initials' => 'UG', 'imageKey' => 'kumasiCulturalTour', 'image' => '/images/home/manhyia_palace.jpg'],
            ['id' => 'returning-client', 'quote' => 'The Akosombo boat cruise and Cape Coast experience were unforgettable. We\'ll definitely book again.', 'name' => 'Returning Client', 'role' => 'Adventure tour guest', 'rating' => '5.0', 'tour' => 'Akosombo & Cape Coast', 'initials' => 'RC', 'imageKey' => 'akosomboBoatCruise', 'image' => '/images/home/waterfall.jpg'],
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
            $sectionContent = $content[$section] ?? [];
            $merged[$section] = array_merge($fields, is_array($sectionContent) ? $sectionContent : []);

            if (isset($fields['items']) && is_array($fields['items'])) {
                $merged[$section]['items'] = static::mergeItems(
                    $fields['items'],
                    is_array($sectionContent['items'] ?? null) ? $sectionContent['items'] : null
                );
            }
        }

        return $merged;
    }

    protected static function mergeItems(array $defaults, ?array $overrides): array
    {
        if (! is_array($overrides) || $overrides === []) {
            return $defaults;
        }

        $byId = [];
        foreach ($defaults as $item) {
            if (! empty($item['id'])) {
                $byId[$item['id']] = $item;
            }
        }

        $order = [];
        foreach ($overrides as $item) {
            if (! is_array($item) || empty($item['id'])) {
                continue;
            }

            $byId[$item['id']] = array_merge($byId[$item['id']] ?? [], $item);
            $order[] = $item['id'];
        }

        foreach ($defaults as $item) {
            if (! empty($item['id']) && ! in_array($item['id'], $order, true)) {
                $order[] = $item['id'];
            }
        }

        return array_values(array_filter(array_map(fn ($id) => $byId[$id] ?? null, $order)));
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
