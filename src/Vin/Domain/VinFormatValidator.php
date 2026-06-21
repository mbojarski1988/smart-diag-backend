<?php

declare(strict_types=1);

namespace App\Vin\Domain;

final class VinFormatValidator
{
    public function normalize(string $vin): string
    {
        return strtoupper(trim($vin));
    }

    public function isValid(string $vin): bool
    {
        return preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $this->normalize($vin)) === 1;
    }
}
