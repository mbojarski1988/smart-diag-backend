<?php

declare(strict_types=1);

namespace App\Vin\Domain;

use App\Vin\Application\Dto\VinCheckDigitResponse;
use App\Vin\Application\Dto\VinDecodeResponse;
use App\Vin\Domain\Lookup\NorthAmericanLookup;

final readonly class NorthAmericanVinDecoder
{
    public function __construct(
        private VinCheckDigitCalculator $checkDigitCalculator,
        private ModelYearDecoder $modelYearDecoder,
        private WmiDecoder $wmiDecoder,
    ) {
    }

    public function decode(Vin $vin, ?string $wmi): VinDecodeResponse
    {
        $warnings = [];
        $actualCheckDigit = $vin->charAt(8);
        $expectedCheckDigit = $this->checkDigitCalculator->calculate($vin->value);
        $isValidCheckDigit = $expectedCheckDigit === $actualCheckDigit;

        $manufacturer = $this->wmiDecoder->decodeManufacturer($vin->value);
        $isFord = $manufacturer !== null;

        if (!$isFord) {
            $warnings[] = 'VIN does not match the local Ford WMI table.';
        }

        if (!$isValidCheckDigit) {
            $warnings[] = sprintf(
                'Invalid check digit. Expected %s, got %s.',
                $expectedCheckDigit ?? 'none',
                $actualCheckDigit,
            );
        }

        $modelYear = NorthAmericanLookup::getYear($vin->charAt(9)) ?? $this->modelYearDecoder->decode($vin->value);

        return new VinDecodeResponse(
            vin: $vin->value,
            valid: $isValidCheckDigit,
            ford: $isFord,
            wmi: $wmi,
            manufacturer: $manufacturer,
            modelYear: $modelYear,
            plantCode: $vin->charAt(10),
            serialNumber: $vin->substr(11, 6),
            checkDigit: new VinCheckDigitResponse(
                valid: $isValidCheckDigit,
                actual: $actualCheckDigit,
                expected: $expectedCheckDigit,
            ),
            warnings: $warnings,
            region: 'North America',
            model: sprintf('Series/Line: %s', $vin->substr(4, 3)),
            bodyStyle: 'Encoded in the model series',
            engineCode: NorthAmericanLookup::getEngine($vin->charAt(7)) ?? sprintf('Engine code: %s', $vin->charAt(7)),
            assemblyPlant: NorthAmericanLookup::getPlant($vin->charAt(10))
                ?? sprintf('Plant code: %s', $vin->charAt(10)),
            productionYear: $modelYear,
            productionMonth: null,
        );
    }
}
