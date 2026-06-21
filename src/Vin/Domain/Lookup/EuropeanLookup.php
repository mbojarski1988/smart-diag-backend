<?php

declare(strict_types=1);

namespace App\Vin\Domain\Lookup;

final class EuropeanLookup
{
    /** @var array<string, list<string>> */
    private const MONTH_CYCLE = [
        'January'   => ['L', 'C', 'B', 'J'],
        'February'  => ['Y', 'K', 'R', 'U'],
        'March'     => ['S', 'D', 'A', 'M'],
        'April'     => ['T', 'E', 'G', 'P'],
        'May'       => ['J', 'L', 'C', 'B'],
        'June'      => ['U', 'Y', 'K', 'R'],
        'July'      => ['M', 'S', 'D', 'A'],
        'August'    => ['P', 'T', 'E', 'G'],
        'September' => ['B', 'J', 'L', 'C'],
        'October'   => ['R', 'U', 'Y', 'K'],
        'November'  => ['A', 'M', 'S', 'D'],
        'December'  => ['G', 'P', 'T', 'E'],
    ];

    private const PLANTS = [
        'B' => 'Genk, Belgium',
        'C' => 'Saarlouis, Germany',
        'G' => 'Cologne, Germany',
        'P' => 'Valencia, Spain',
    ];

    private const MODELS = [
        'B' => 'Mondeo',
        'C' => 'Fiesta',
        'D' => 'Focus',
        'W' => 'Galaxy/S-Max',
        'M' => 'Kuga',
        'J' => 'Transit',
    ];

    public static function getPlant(string $code): ?string
    {
        return self::PLANTS[$code] ?? null;
    }

    public static function getModel(string $code): ?string
    {
        return self::MODELS[$code] ?? null;
    }

    /**
     * @return array<string, list<string>>
     */
    public static function getMonthCycle(): array
    {
        return self::MONTH_CYCLE;
    }
}
