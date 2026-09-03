<?php

namespace Database\Seeders;

use App\Models\Experience;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Seeds default published experiences from the frontend mock content.
 * Safe to re-run: creates missing keys only; never overwrites existing CMS edits.
 */
class ExperiencesSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $sort = 10;

        foreach ($this->items() as $item) {
            Experience::query()->firstOrCreate(
                ['experience_key' => $item['experience_key']],
                [
                    ...$item,
                    'status' => 'published',
                    'published_at' => $now,
                    'sort_order' => $sort,
                ],
            );
            $sort += 10;
        }
    }

    /** @return list<array<string, mixed>> */
    protected function items(): array
    {
        return [
            [
                'experience_key' => 'heritage',
                'slug' => 'ghana-heritage-history',
                'label' => 'Heritage & History',
                'icon_key' => 'landmark',
                'tagline' => 'Walk Ghana’s corridors of memory',
                'description' => 'Cape Coast Castle, Elmina Castle, and living Ashanti heritage in Kumasi — journeys that deepen understanding of Ghana’s past and present, guided by local experts.',
                'highlights' => ['Cape Coast Castle', 'Elmina Castle', 'Manhyia Palace, Kumasi', 'Ancestral & naming ceremonies'],
                'regions' => ['Central Region', 'Ashanti Region', 'Greater Accra'],
                'keywords' => ['Cape Coast tours', 'Elmina Castle', 'Ghana heritage tour', 'Kumasi cultural tour'],
                'image' => '/images/home/dest-ghana.jpg',
                'badge_text' => 'Most searched',
                'tour_query' => ['region' => 'central'],
                'story_category' => 'Heritage',
                'related_story_slugs' => ['cultural-immersion-newsletter-issue-01'],
            ],
            [
                'experience_key' => 'accra',
                'slug' => 'accra-city-culture',
                'label' => 'Accra City & Culture',
                'icon_key' => 'building',
                'tagline' => 'Feel the pulse of Ghana’s capital',
                'description' => 'Independence Square, arts markets, nightlife, and creative Accra — ideal as a first day or a full city immersion before coastal and inland adventures.',
                'highlights' => ['City landmarks', 'Arts & crafts markets', 'Food & nightlife', 'Creative neighborhoods'],
                'regions' => ['Greater Accra'],
                'keywords' => ['Accra city tour', 'things to do in Accra', 'Accra cultural tour'],
                'image' => '/images/home/arts_and_craft.jpg',
                'badge_text' => 'City starter',
                'tour_query' => ['region' => 'greater-accra'],
                'story_category' => 'Culture',
                'related_story_slugs' => [],
            ],
            [
                'experience_key' => 'adventure',
                'slug' => 'volta-adventure-nature',
                'label' => 'Adventure & Nature',
                'icon_key' => 'mountain',
                'tagline' => 'Waterfalls, canopy walks, and open trails',
                'description' => 'Wli Falls, Boti Falls, Kakum canopy walk, Shai Hills, and Volta Region treks — active Ghana travel with scenery and local guides who know the routes.',
                'highlights' => ['Wli Waterfalls', 'Kakum Canopy Walk', 'Shai Hills', 'Volta Region hiking'],
                'regions' => ['Volta Region', 'Eastern Region', 'Central Region'],
                'keywords' => ['Volta Region tours', 'Kakum National Park', 'Wli Falls tour', 'Ghana adventure travel'],
                'image' => '/images/home/volta.jpg',
                'badge_text' => 'Active travel',
                'tour_query' => ['region' => 'volta'],
                'story_category' => 'Adventure',
                'related_story_slugs' => [],
            ],
            [
                'experience_key' => 'coast',
                'slug' => 'ghana-coast-beach',
                'label' => 'Coast & Beach',
                'icon_key' => 'waves',
                'tagline' => 'Atlantic shores with culture nearby',
                'description' => 'Ada Foah estuary, Labadi Beach, and Western Region coastlines — pair beach time with fishing villages, stilt communities, and sunset boat rides.',
                'highlights' => ['Ada Foah', 'Labadi Beach', 'Nzulezu Stilt Village', 'Coastal day trips'],
                'regions' => ['Greater Accra', 'Western Region'],
                'keywords' => ['Ada Foah tours', 'Ghana beach holiday', 'Nzulezu stilt village'],
                'image' => '/images/home/hero_two.jpg',
                'badge_text' => 'Relaxation',
                'tour_query' => [],
                'story_category' => 'Culture',
                'related_story_slugs' => [],
            ],
            [
                'experience_key' => 'cultural',
                'slug' => 'cultural-immersion-ghana',
                'label' => 'Cultural Immersion',
                'icon_key' => 'drama',
                'tagline' => 'Live it — don’t just observe',
                'description' => 'Kente weaving in Bonwire, drumming workshops, market walks, and community visits. Participatory experiences designed for travelers who want authentic connection.',
                'highlights' => ['Kente weaving, Bonwire', 'Drumming sessions', 'Market immersion', 'Community visits'],
                'regions' => ['Ashanti Region', 'Greater Accra', 'Volta Region'],
                'keywords' => ['cultural immersion Ghana', 'kente weaving tour', 'Ghana drumming experience'],
                'image' => '/images/home/ghana_tour.png',
                'badge_text' => 'Hands-on',
                'tour_query' => [],
                'story_category' => 'Culture',
                'related_story_slugs' => ['cultural-immersion-newsletter-issue-01'],
            ],
            [
                'experience_key' => 'university',
                'slug' => 'university-educational-tours-ghana',
                'label' => 'University & Educational Tours',
                'icon_key' => 'graduation',
                'tagline' => 'Study Ghana, not just visit it',
                'description' => 'Curriculum-aligned itineraries for universities and schools — heritage sites, guest lectures, safe group logistics, and faculty co-design with 360 Tours.',
                'highlights' => ['Curriculum-aligned routes', 'Faculty co-design', 'Guest lectures', 'Group logistics'],
                'regions' => ['Nationwide'],
                'keywords' => ['university study abroad Ghana', 'educational tours Ghana', 'school trip Ghana'],
                'image' => '/images/home/hero_three.jpg',
                'badge_text' => 'Groups',
                'tour_query' => ['tourType' => 'custom'],
                'story_category' => 'Corporate',
                'related_story_slugs' => [],
            ],
            [
                'experience_key' => 'corporate',
                'slug' => 'corporate-retreats-ghana',
                'label' => 'Corporate & Team Retreats',
                'icon_key' => 'briefcase',
                'tagline' => 'Team building with Ghanaian soul',
                'description' => 'Leadership workshops, community impact days, and scenic retreat bases — end-to-end coordination for corporate groups visiting Ghana.',
                'highlights' => ['Team leadership modules', 'Impact days', 'Flexible group sizing', 'Full logistics'],
                'regions' => ['Nationwide'],
                'keywords' => ['corporate retreat Ghana', 'team building Accra', 'company offsite Ghana'],
                'image' => '/images/home/hero_four.png',
                'badge_text' => 'Business',
                'tour_query' => ['tourType' => 'custom'],
                'story_category' => 'Corporate',
                'related_story_slugs' => [],
            ],
            [
                'experience_key' => 'custom',
                'slug' => 'custom-ghana-itinerary',
                'label' => 'Custom Ghana Itineraries',
                'icon_key' => 'route',
                'tagline' => 'Built around your dates and interests',
                'description' => 'Private families, diaspora homecomings, multi-stop Ghana circuits — tell us your dates and we design tours, stays, and transport as one plan.',
                'highlights' => ['Private & family trips', 'Diaspora homecoming', 'Multi-region circuits', 'Stays & transport included'],
                'regions' => ['Nationwide'],
                'keywords' => ['custom Ghana itinerary', 'private Ghana tour', 'diaspora travel Ghana'],
                'image' => '/images/home/hero.jpg',
                'badge_text' => 'Tailor-made',
                'tour_query' => ['tourType' => 'custom'],
                'story_category' => 'Newsletter',
                'related_story_slugs' => [],
            ],
        ];
    }
}
