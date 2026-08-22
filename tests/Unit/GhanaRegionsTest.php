<?php

namespace Tests\Unit;

use App\Support\GhanaRegions;
use PHPUnit\Framework\TestCase;

class GhanaRegionsTest extends TestCase
{
    public function test_resolves_region_from_city_and_region_string(): void
    {
        $this->assertSame('central', GhanaRegions::resolveFromLocation('Cape Coast, Central'));
        $this->assertSame('ashanti', GhanaRegions::resolveFromLocation('Kumasi, Ashanti Region'));
        $this->assertSame('greater-accra', GhanaRegions::resolveFromLocation('Accra, Greater Accra'));
    }

    public function test_falls_back_to_town_lookup_for_legacy_city_only_values(): void
    {
        $this->assertSame('volta', GhanaRegions::resolveFromLocation('Wli'));
        $this->assertSame('eastern', GhanaRegions::resolveFromLocation('Akosombo'));
        $this->assertSame('western', GhanaRegions::resolveFromLocation('Nzulezu'));
    }

    public function test_unknown_and_empty_locations_resolve_to_null(): void
    {
        $this->assertNull(GhanaRegions::resolveFromLocation('Lagos, Nigeria'));
        $this->assertNull(GhanaRegions::resolveFromLocation('   '));
        $this->assertNull(GhanaRegions::resolveFromLocation(null));
    }

    public function test_location_list_becomes_distinct_ordered_region_ids(): void
    {
        $regions = GhanaRegions::resolveFromLocations([
            'Accra, Greater Accra',
            'Cape Coast, Central',
            'Elmina, Central',
            'Nairobi, Kenya',
            null,
            ['not-a-string'],
        ]);

        $this->assertSame(['greater-accra', 'central'], $regions);
    }

    public function test_labels_and_existence_checks(): void
    {
        $this->assertSame('Volta', GhanaRegions::label('volta'));
        $this->assertSame('', GhanaRegions::label('atlantis'));
        $this->assertTrue(GhanaRegions::exists('upper-west'));
        $this->assertFalse(GhanaRegions::exists('upper-middle'));
    }

    public function test_options_expose_every_region_as_id_label_pairs(): void
    {
        $options = GhanaRegions::options();

        $this->assertCount(count(GhanaRegions::REGIONS), $options);
        $this->assertSame(['id' => 'greater-accra', 'label' => 'Greater Accra'], $options[0]);
    }
}
