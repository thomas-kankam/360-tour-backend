<?php

namespace Database\Seeders;

use App\Models\Story;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Seeds default published stories from the frontend mock journal content.
 * Safe to re-run: creates missing slugs only; never overwrites existing CMS edits.
 */
class StoriesSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $sort = 10;

        foreach ($this->items() as $item) {
            Story::query()->firstOrCreate(
                ['slug' => $item['slug']],
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
                'slug' => 'cultural-immersion-newsletter-issue-01',
                'title' => 'Cultural Immersion Newsletter, Issue 01: Connecting Africa to the World',
                'excerpt' => 'Welcome to the 360 Tours inaugural Cultural Immersion Newsletter. Discover our African Cultural Travel Series and join a community of travelers, explorers, and cultural ambassadors.',
                'category' => 'Newsletter',
                'country' => 'Pan-African',
                'author' => '360 Tours',
                'author_role' => 'Editorial Team',
                'display_date' => 'November 2025',
                'read_time' => '8 min read',
                'image' => '/images/home/hero.jpg',
                'body' => [
                    ['type' => 'lead', 'text' => 'Discover Africa. Travel Without Limits.'],
                    ['type' => 'heading', 'text' => 'Who We Are'],
                    ['type' => 'paragraph', 'text' => 'Welcome to 360 Tours and Investment Limited, your trusted partner for guided tours, accommodation, and transport across Ghana and beyond. We combine local expertise with professional coordination for seamless journeys.'],
                    ['type' => 'paragraph', 'text' => 'More than a travel company, we help you explore Ghana and beyond with confidence.'],
                    ['type' => 'quote', 'text' => 'To create journeys that go beyond sightseeing — experiences that celebrate culture, connect travelers to Ghana and Africa, and leave a lasting impact.'],
                    ['type' => 'paragraph', 'text' => 'Based in Accra, Ghana, with coordination support in Amsterdam, we serve travelers worldwide with end-to-end tour planning, stays, and transport.'],
                    ['type' => 'heading', 'text' => 'We Are Excited!'],
                    ['type' => 'paragraph', 'text' => '360 Tours continues to expand curated departures across Ghana — from Cape Coast heritage routes to Volta adventures and Accra city experiences — helping travelers experience Africa authentically.'],
                    ['type' => 'paragraph', 'text' => "Whether you're a university planning a global exchange, a company seeking an adventure retreat, or an individual ready to discover Africa's heartbeat, 360 Tours is your trusted partner."],
                    ['type' => 'quote', 'text' => 'This is not just travel. It is discovery.'],
                    ['type' => 'heading', 'text' => 'Our African Story'],
                    ['type' => 'paragraph', 'text' => '360 Tours began with a simple goal: make African travel accessible, authentic, and well organized. From heritage tours in Ghana to curated experiences across the continent, we design trips that combine exploration, culture, and genuine connection.'],
                    ['type' => 'paragraph', 'text' => "When you travel with 360 Tours, you don't just visit Africa — you feel its rhythm, taste its flavors, and connect with its people."],
                    ['type' => 'heading', 'text' => 'Upcoming Destinations for 2025'],
                    ['type' => 'list', 'items' => [
                        '🌴 Ghana, 10 Day Classic Heritage Tour',
                        '🦒 Kenya, 9 Day Safari & Culture Expedition',
                        '🌊 South Africa, Cape Town & Johannesburg Discovery',
                    ]],
                    ['type' => 'heading', 'text' => 'Join the Movement'],
                    ['type' => 'paragraph', 'text' => "Become part of the 360 Tours community of travelers, explorers, and cultural ambassadors. Follow our stories, join our programs, and let's explore Africa together, one journey at a time."],
                ],
            ],
            [
                'slug' => 'cape-coast-castle-reflection',
                'title' => 'Standing Inside Cape Coast Castle: A Reflection on Memory and Healing',
                'excerpt' => "Our group of 22 students stood in the dungeons of Cape Coast Castle, a silence fell that no guide's words could fill. This is what happened next.",
                'category' => 'Heritage',
                'country' => 'Ghana',
                'author' => 'Dr. Amara Williams',
                'author_role' => 'University program coordinator',
                'display_date' => 'March 18, 2025',
                'read_time' => '6 min read',
                'image' => '/images/home/dest-ghana.jpg',
                'body' => [
                    ['type' => 'paragraph', 'text' => "Our group of 22 students stood in the dungeons of Cape Coast Castle, a silence fell that no guide's words could fill. The weight of history pressed against the stone walls, and for a moment, the Atlantic itself seemed to hold its breath."],
                    ['type' => 'paragraph', 'text' => "360 Tours local guides didn't rush us through. They gave us space to feel, to reflect, and to connect with a past that shaped nations. That is what cultural immersion means, not observation, but presence."],
                ],
            ],
            [
                'slug' => 'maasai-mara-dawn-drive',
                'title' => 'Dawn on the Mara: Why the First Game Drive Changes Everything',
                'excerpt' => 'The alarm says 5:30am. You groan. Then the Land Cruiser crests a ridge and ten thousand wildebeest appear below you in the morning mist.',
                'category' => 'Safari',
                'country' => 'Kenya',
                'author' => 'James Okonkwo',
                'author_role' => 'Travel writer',
                'display_date' => 'April 2, 2025',
                'read_time' => '5 min read',
                'image' => '/images/home/dest-kenya.jpg',
                'body' => [
                    ['type' => 'paragraph', 'text' => 'The alarm says 5:30am. You groan. Then the Land Cruiser crests a ridge and ten thousand wildebeest appear below you in the morning mist, and every complaint disappears.'],
                ],
            ],
            [
                'slug' => 'kente-weaving-bonwire',
                'title' => 'Threads of Identity: Learning to Weave Kente in Bonwire',
                'excerpt' => 'Each pattern in Kente cloth carries a proverb, a lineage, a belief. Sitting at a loom in Bonwire, I learned that fabric can be a language.',
                'category' => 'Culture',
                'country' => 'Ghana',
                'author' => 'Patricia Mensah',
                'author_role' => 'Heritage educator',
                'display_date' => 'February 10, 2025',
                'read_time' => '4 min read',
                'image' => '/images/home/ghana_tour.png',
                'body' => [
                    ['type' => 'paragraph', 'text' => 'Each pattern in Kente cloth carries a proverb, a lineage, a belief. Sitting at a loom in Bonwire with a master weaver, I learned that fabric can be a language, and Africa speaks fluently to those who listen.'],
                ],
            ],
            [
                'slug' => 'soweto-walking-tour-perspective',
                'title' => 'Soweto on Foot: What No Bus Tour Can Show You',
                'excerpt' => 'Walking through Vilakazi Street, the only street in the world to have housed two Nobel Peace Prize winners, you feel the weight of courage underfoot.',
                'category' => 'Heritage',
                'country' => 'South Africa',
                'author' => 'Nathan Reed',
                'author_role' => 'Faculty lead',
                'display_date' => 'May 5, 2025',
                'read_time' => '7 min read',
                'image' => '/images/home/dest-sa.jpg',
                'body' => [
                    ['type' => 'paragraph', 'text' => 'Walking through Vilakazi Street, the only street in the world to have housed two Nobel Peace Prize winners, you feel the weight of courage underfoot. Soweto on foot is history made tangible.'],
                ],
            ],
            [
                'slug' => 'table-mountain-sunrise',
                'title' => "Table Mountain at Sunrise: Africa's Most Spectacular First Hour",
                'excerpt' => 'We started our hike at 4am, headlamps cutting the fynbos. By the time the sun rose over Cape Town, every breathless step made sense.',
                'category' => 'Adventure',
                'country' => 'South Africa',
                'author' => 'Sarah Mitchell',
                'author_role' => 'Group travel organizer',
                'display_date' => 'January 22, 2025',
                'read_time' => '4 min read',
                'image' => '/images/home/sa_tour.png',
                'body' => [
                    ['type' => 'paragraph', 'text' => 'We started our hike at 4am, headlamps cutting the fynbos. By the time the sun rose over Cape Town, every breathless step made sense.'],
                ],
            ],
            [
                'slug' => 'accra-food-scene',
                'title' => "Eating Accra: A Guide to Ghana's Most Underrated Culinary Capital",
                'excerpt' => "Jollof rice debates aside, Accra's food scene, from Labadi beach grills to the Oxford Street chop bars, is one of West Africa's best-kept secrets.",
                'category' => 'Culture',
                'country' => 'Ghana',
                'author' => 'Emily Kariuki',
                'author_role' => 'Food & culture writer',
                'display_date' => 'March 30, 2025',
                'read_time' => '5 min read',
                'image' => '/images/home/hero.jpg',
                'body' => [
                    ['type' => 'paragraph', 'text' => "Jollof rice debates aside, Accra's food scene, from Labadi beach grills to the Oxford Street chop bars, is one of West Africa's best-kept secrets."],
                ],
            ],
            [
                'slug' => 'nairobi-beyond-safari',
                'title' => 'Nairobi Beyond the Safari: A City That Rewards Slow Travel',
                'excerpt' => 'Most visitors treat Nairobi as a layover. Those who stay discover a creative, culinary, and cultural capital that rivals any African city.',
                'category' => 'Culture',
                'country' => 'Kenya',
                'author' => 'James Okonkwo',
                'author_role' => 'Travel writer',
                'display_date' => 'April 15, 2025',
                'read_time' => '6 min read',
                'image' => '/images/home/kenyan_tour.png',
                'body' => [
                    ['type' => 'paragraph', 'text' => 'Most visitors treat Nairobi as a layover. Those who stay discover a creative, culinary, and cultural capital that rivals any African city.'],
                ],
            ],
            [
                'slug' => 'corporate-retreat-kenya',
                'title' => 'How a Kenya Retreat Reset Our Entire Leadership Team',
                'excerpt' => 'We came for the game drives. We left with a shared language, a deeper trust, and a clarity about what we were building, together.',
                'category' => 'Corporate',
                'country' => 'Kenya',
                'author' => 'Sarah Mitchell',
                'author_role' => 'Group travel organizer',
                'display_date' => 'February 28, 2025',
                'read_time' => '5 min read',
                'image' => '/images/home/dest-kenya.jpg',
                'body' => [
                    ['type' => 'paragraph', 'text' => 'We came for the game drives. We left with a shared language, a deeper trust, and a clarity about what we were building, together.'],
                ],
            ],
            [
                'slug' => 'kruger-big five',
                'title' => 'The Big Five in 48 Hours: Is It Really Possible?',
                'excerpt' => 'Our Kruger guide laughed when we asked. Then, in two extraordinary days, we checked all five off the list, and understood why every single one matters.',
                'category' => 'Safari',
                'country' => 'South Africa',
                'author' => 'Nathan Reed',
                'author_role' => 'Faculty lead',
                'display_date' => 'May 12, 2025',
                'read_time' => '4 min read',
                'image' => '/images/home/dest-sa.jpg',
                'body' => [
                    ['type' => 'paragraph', 'text' => 'Our Kruger guide laughed when we asked. Then, in two extraordinary days, we checked all five off the list, and understood why every single one matters.'],
                ],
            ],
        ];
    }
}
