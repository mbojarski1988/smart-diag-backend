<?php

declare(strict_types=1);

namespace App\Vin\Domain\Lookup;

final class NorthAmericanLookup
{
    private const YEAR_MAP = [
        'Y' => 2000, '1' => 2001, '2' => 2002, '3' => 2003, '4' => 2004,
        '5' => 2005, '6' => 2006, '7' => 2007, '8' => 2008, '9' => 2009,
        'A' => 2010, 'B' => 2011, 'C' => 2012, 'D' => 2013, 'E' => 2014,
        'F' => 2015, 'G' => 2016, 'H' => 2017, 'J' => 2018, 'K' => 2019,
        'L' => 2020, 'M' => 2021, 'N' => 2022, 'P' => 2023, 'R' => 2024,
        'S' => 2025, 'T' => 2026, 'V' => 2027, 'W' => 2028, 'X' => 2029,
    ];

    private const PLANTS = [
        '5' => 'Flat Rock, MI (Mustang)',
        'F' => 'Dearborn, MI (F-150)',
        'K' => 'Kansas City, MO (F-150)',
        'L' => 'Wayne, MI (Bronco/Ranger)',
        'E' => 'Louisville, KY (Escape/Super Duty)',
        'R' => 'Hermosillo, Mexico',
    ];

    private const ENGINES = [
        'F' => '5.0L V8 (Coyote)',
        'H' => '5.4L V8',
        '9' => '2.0L EcoBoost',
        'G' => '3.5L V6 EcoBoost',
        'T' => '2.3L EcoBoost',
        'W' => '4.6L V8',
    ];

    public static function getYear(string $code): ?int
    {
        return self::YEAR_MAP[$code] ?? null;
    }

    public static function getPlant(string $code): ?string
    {
        return self::PLANTS[$code] ?? null;
    }

    public static function getEngine(string $code): ?string
    {
        return self::ENGINES[$code] ?? null;
    }
}
