<?php

declare(strict_types=1);

namespace App\Vin\Domain;

final class VinCheckDigitCalculator
{
    private const TRANSLITERATION = [
        'A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5, 'F' => 6, 'G' => 7, 'H' => 8,
        'J' => 1, 'K' => 2, 'L' => 3, 'M' => 4, 'N' => 5, 'P' => 7, 'R' => 9,
        'S' => 2, 'T' => 3, 'U' => 4, 'V' => 5, 'W' => 6, 'X' => 7, 'Y' => 8, 'Z' => 9,
        '0' => 0, '1' => 1, '2' => 2, '3' => 3, '4' => 4,
        '5' => 5, '6' => 6, '7' => 7, '8' => 8, '9' => 9,
    ];

    private const WEIGHTS = [8, 7, 6, 5, 4, 3, 2, 10, 0, 9, 8, 7, 6, 5, 4, 3, 2];

    public function calculate(string $vin): ?string
    {
        $vin = strtoupper(trim($vin));

        if (strlen($vin) !== 17) {
            return null;
        }

        $sum = 0;

        for ($i = 0; $i < 17; $i++) {
            $char = $vin[$i];

            if (!array_key_exists($char, self::TRANSLITERATION)) {
                return null;
            }

            $sum += self::TRANSLITERATION[$char] * self::WEIGHTS[$i];
        }

        $remainder = $sum % 11;

        return $remainder === 10 ? 'X' : (string) $remainder;
    }

    public function isValid(string $vin): bool
    {
        $expected = $this->calculate($vin);

        return $expected !== null && strtoupper($vin[8]) === $expected;
    }
}
