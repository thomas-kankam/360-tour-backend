<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeds Stories + Experiences CMS content from the former frontend mock data.
 * Does not wipe existing rows — only inserts missing slugs/keys.
 */
class ContentCmsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            StoriesSeeder::class,
            ExperiencesSeeder::class,
        ]);
    }
}
