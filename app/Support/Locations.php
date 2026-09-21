<?php

namespace App\Support;

/**
 * Mongolian administrative units: Ulaanbaatar (districts + khoroos) and the
 * 21 aimags with their soums. Source file: resources/data/mn-locations.json.
 */
class Locations
{
    protected static ?array $data = null;

    public static function all(): array
    {
        return static::$data ??= json_decode(file_get_contents(resource_path('data/mn-locations.json')), true);
    }

    public static function province(string $name): ?array
    {
        foreach (static::all()['provinces'] as $p) {
            if ($p['name'] === $name) {
                return $p;
            }
        }

        return null;
    }

    /** True when the district/soum belongs to the province. */
    public static function isValid(string $province, string $district, ?string $khoroo = null): bool
    {
        $p = static::province($province);
        if (! $p) {
            return false;
        }

        if ($p['type'] === 'city') {
            foreach ($p['districts'] as $d) {
                if ($d['name'] === $district) {
                    return $khoroo === null || $khoroo === '' || (ctype_digit((string) $khoroo) && (int) $khoroo >= 1 && (int) $khoroo <= $d['khoroos']);
                }
            }

            return false;
        }

        return in_array($district, $p['soums'], true);
    }

    public static function districtLabel(string $province): string
    {
        return $province === 'Улаанбаатар' ? 'Дүүрэг' : 'Сум';
    }

    public static function khorooLabel(string $province): string
    {
        return $province === 'Улаанбаатар' ? 'Хороо' : 'Баг';
    }
}
