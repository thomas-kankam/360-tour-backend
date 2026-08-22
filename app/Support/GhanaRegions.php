<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Ghana's 16 administrative regions plus the tourist towns that sit in each one.
 *
 * Tour locations are captured on the frontend as "City, Region" strings, so this
 * class turns those free-text values into stable region ids that the public
 * listings endpoint can filter on.
 */
class GhanaRegions
{
    /** @var array<string, string> region id => display label */
    public const REGIONS = [
        'greater-accra' => 'Greater Accra',
        'central' => 'Central',
        'ashanti' => 'Ashanti',
        'volta' => 'Volta',
        'eastern' => 'Eastern',
        'western' => 'Western',
        'western-north' => 'Western North',
        'northern' => 'Northern',
        'north-east' => 'North East',
        'upper-east' => 'Upper East',
        'upper-west' => 'Upper West',
        'savannah' => 'Savannah',
        'bono' => 'Bono',
        'bono-east' => 'Bono East',
        'ahafo' => 'Ahafo',
        'oti' => 'Oti',
    ];

    /** @var array<string, string[]> region id => notable towns */
    public const TOWNS = [
        'greater-accra' => ['Accra', 'Tema', 'Ada Foah', 'Labadi', 'Teshie', 'Shai Hills'],
        'central' => ['Cape Coast', 'Elmina', 'Kakum', 'Winneba', 'Kasoa'],
        'ashanti' => ['Kumasi', 'Obuasi', 'Ejisu', 'Mampong', 'Konongo', 'Bonwire'],
        'volta' => ['Ho', 'Hohoe', 'Wli', 'Tafi Atome', 'Keta'],
        'eastern' => ['Koforidua', 'Aburi', 'Akosombo', 'Mpraeso', 'Nkawkaw', 'Boti'],
        'western' => ['Takoradi', 'Sekondi', 'Axim', 'Elubo', 'Busua', 'Nzulezu'],
        'western-north' => ['Sefwi Wiawso', 'Bibiani', 'Enchi', 'Juaboso'],
        'northern' => ['Tamale', 'Yendi', 'Salaga', 'Damongo', 'Mole'],
        'north-east' => ['Nalerigu', 'Walewale', 'Gambaga', 'Bunkpurugu'],
        'upper-east' => ['Bolgatanga', 'Navrongo', 'Paga', 'Bawku'],
        'upper-west' => ['Wa', 'Lawra', 'Tumu', 'Jirapa'],
        'savannah' => ['Damongo', 'Bole', 'Buipe'],
        'bono' => ['Sunyani', 'Berekum', 'Dormaa Ahenkro', 'Wenchi'],
        'bono-east' => ['Techiman', 'Kintampo', 'Nkoranza', 'Atebubu'],
        'ahafo' => ['Goaso', 'Tepa', 'Hwidiem', 'Bechem'],
        'oti' => ['Dambai', 'Jasikan', 'Kete Krachi', 'Nkwanta'],
    ];

    public static function label(?string $regionId): string
    {
        return self::REGIONS[(string) $regionId] ?? '';
    }

    public static function exists(?string $regionId): bool
    {
        return isset(self::REGIONS[(string) $regionId]);
    }

    /**
     * Resolves a single location string to a region id.
     * Matches the region name first (locations are saved as "City, Region"),
     * then falls back to a known-town lookup for legacy city-only values.
     */
    public static function resolveFromLocation(?string $location): ?string
    {
        $value = trim((string) $location);
        if ($value === '') {
            return null;
        }

        $needle = Str::lower($value);

        foreach (self::REGIONS as $id => $label) {
            if (str_contains($needle, Str::lower($label))) {
                return $id;
            }
        }

        foreach (self::TOWNS as $id => $towns) {
            foreach ($towns as $town) {
                if (str_contains($needle, Str::lower($town))) {
                    return $id;
                }
            }
        }

        return null;
    }

    /**
     * Derives the distinct, ordered region ids covered by a tour's location list.
     *
     * @param  array<int, mixed>  $locations
     * @return string[]
     */
    public static function resolveFromLocations(array $locations): array
    {
        $regions = [];

        foreach ($locations as $location) {
            if (! is_string($location) && ! is_numeric($location)) {
                continue;
            }

            $region = self::resolveFromLocation((string) $location);
            if ($region && ! in_array($region, $regions, true)) {
                $regions[] = $region;
            }
        }

        return $regions;
    }

    /** @return array<int, array{id: string, label: string}> */
    public static function options(): array
    {
        return array_map(
            fn (string $id, string $label) => ['id' => $id, 'label' => $label],
            array_keys(self::REGIONS),
            array_values(self::REGIONS),
        );
    }
}
