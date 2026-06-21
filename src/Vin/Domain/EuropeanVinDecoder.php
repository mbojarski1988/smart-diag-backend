<?php

declare(strict_types=1);

namespace App\Vin\Domain;

use App\Vin\Application\Dto\VinCheckDigitResponse;
use App\Vin\Application\Dto\VinDecodeResponse;
use App\Vin\Domain\Lookup\EuropeanLookup;
use App\Vin\Domain\Lookup\NorthAmericanLookup;

final readonly class EuropeanVinDecoder
{
    public function decode(Vin $vin, string $wmi): VinDecodeResponse
    {
        $year = NorthAmericanLookup::getYear($vin->charAt(10));
        $plantCode = $vin->charAt(7);
        $modelCode = $vin->charAt(8);

        return new VinDecodeResponse(
            vin: $vin->value,
            valid: true,
            ford: true,
            wmi: $wmi,
            manufacturer: 'Ford Werke AG (Europe)',
            modelYear: $year,
            plantCode: $plantCode,
            serialNumber: $vin->substr(12, 5),
            checkDigit: new VinCheckDigitResponse(
                valid: true,
                actual: $vin->charAt(8),
                expected: null,
            ),
            warnings: [],
            region: 'Europe',
            model: EuropeanLookup::getModel($modelCode) ?? sprintf('Unknown (%s)', $modelCode),
            bodyStyle: sprintf('Code: %s', $vin->charAt(3)),
            engineCode: null,
            assemblyPlant: EuropeanLookup::getPlant($plantCode) ?? sprintf('Plant code: %s', $plantCode),
            productionYear: $year,
            productionMonth: $year !== null ? $this->decodeMonth($year, $vin->charAt(11)) : null,
        );
    }

    private function decodeMonth(int $year, string $monthCode): string
    {
        if ($year < 2010) {
            return 'Unknown';
        }

        $cycleIndex = ($year - 2010) % 4;

        foreach (EuropeanLookup::getMonthCycle() as $month => $codes) {
            if ($codes[$cycleIndex] === $monthCode) {
                return $month;
            }
        }

        return 'Unknown';
    }
}
