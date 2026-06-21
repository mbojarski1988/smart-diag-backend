<?php

declare(strict_types=1);

namespace App\Vin\Domain;

use App\Vin\Application\Dto\VinCheckDigitResponse;
use App\Vin\Application\Dto\VinDecodeResponse;

final readonly class FordVinDecoder
{
    public function __construct(
        private VinFormatValidator $formatValidator,
        private WmiDecoder $wmiDecoder,
        private EuropeanVinDecoder $europeanVinDecoder,
        private NorthAmericanVinDecoder $northAmericanVinDecoder,
    ) {
    }

    public function decode(string $rawVin): VinDecodeResponse
    {
        $normalized = $this->formatValidator->normalize($rawVin);

        if (!$this->formatValidator->isValid($normalized)) {
            return new VinDecodeResponse(
                vin: $normalized,
                valid: false,
                ford: false,
                wmi: strlen($normalized) >= 3 ? substr($normalized, 0, 3) : null,
                manufacturer: null,
                modelYear: null,
                plantCode: null,
                serialNumber: null,
                checkDigit: new VinCheckDigitResponse(
                    valid: false,
                    actual: strlen($normalized) >= 9 ? $normalized[8] : null,
                    expected: null,
                ),
                warnings: ['Invalid VIN format. VIN must contain 17 characters and cannot include I, O, or Q.'],
                errorMessage: 'A 17-character VIN without I, O, or Q is required.',
            );
        }

        $vin = Vin::fromString($normalized);
        $wmi = $this->wmiDecoder->getWmi($normalized);

        if ($wmi !== null && str_starts_with($wmi, 'WF')) {
            return $this->europeanVinDecoder->decode($vin, $wmi);
        }

        if ($this->wmiDecoder->requiresCheckDigit($normalized)) {
            return $this->northAmericanVinDecoder->decode($vin, $wmi);
        }

        return new VinDecodeResponse(
            vin: $normalized,
            valid: false,
            ford: false,
            wmi: $wmi,
            manufacturer: null,
            modelYear: null,
            plantCode: null,
            serialNumber: null,
            checkDigit: new VinCheckDigitResponse(
                valid: false,
                actual: $normalized[8],
                expected: null,
            ),
            warnings: ['This region is not supported.'],
            errorMessage: 'This region is not supported.',
        );
    }
}
