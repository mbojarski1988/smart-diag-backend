<?php

declare(strict_types=1);

namespace App\Vin\Application\Dto;

final readonly class VinDecodeResponse
{
    /**
     * @param list<string> $warnings
     * @param array<string, mixed> $external
     */
    public function __construct(
        public string $vin,
        public bool $valid,
        public bool $ford,
        public ?string $wmi,
        public ?string $manufacturer,
        public ?int $modelYear,
        public ?string $plantCode,
        public ?string $serialNumber,
        public VinCheckDigitResponse $checkDigit,
        public array $warnings = [],
        public array $external = [],
        public ?string $region = null,
        public ?string $model = null,
        public ?string $bodyStyle = null,
        public ?string $engineCode = null,
        public ?string $assemblyPlant = null,
        public ?int $productionYear = null,
        public ?string $productionMonth = null,
        public ?string $errorMessage = null,
    ) {
    }

    /**
     * @param array{
     *     vin: string,
     *     valid: bool,
     *     ford: bool,
     *     wmi: string|null,
     *     manufacturer: string|null,
     *     modelYear: int|null,
     *     plantCode: string|null,
     *     serialNumber: string|null,
     *     region: string|null,
     *     model: string|null,
     *     bodyStyle: string|null,
     *     engineCode: string|null,
     *     assemblyPlant: string|null,
     *     productionYear: int|null,
     *     productionMonth: string|null,
     *     errorMessage: string|null,
     *     checkDigit: array{valid: bool, actual: string|null, expected: string|null},
     *     warnings: list<string>,
     *     external: array<string, mixed>
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            vin: $data['vin'],
            valid: $data['valid'],
            ford: $data['ford'],
            wmi: $data['wmi'],
            manufacturer: $data['manufacturer'],
            modelYear: $data['modelYear'],
            plantCode: $data['plantCode'],
            serialNumber: $data['serialNumber'],
            checkDigit: VinCheckDigitResponse::fromArray($data['checkDigit']),
            warnings: $data['warnings'],
            external: $data['external'],
            region: $data['region'],
            model: $data['model'],
            bodyStyle: $data['bodyStyle'],
            engineCode: $data['engineCode'],
            assemblyPlant: $data['assemblyPlant'],
            productionYear: $data['productionYear'],
            productionMonth: $data['productionMonth'],
            errorMessage: $data['errorMessage'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'vin' => $this->vin,
            'valid' => $this->valid,
            'ford' => $this->ford,
            'wmi' => $this->wmi,
            'manufacturer' => $this->manufacturer,
            'modelYear' => $this->modelYear,
            'plantCode' => $this->plantCode,
            'serialNumber' => $this->serialNumber,
            'region' => $this->region,
            'model' => $this->model,
            'bodyStyle' => $this->bodyStyle,
            'engineCode' => $this->engineCode,
            'assemblyPlant' => $this->assemblyPlant,
            'productionYear' => $this->productionYear,
            'productionMonth' => $this->productionMonth,
            'errorMessage' => $this->errorMessage,
            'checkDigit' => $this->checkDigit->toArray(),
            'warnings' => $this->warnings,
            'external' => $this->external,
        ];
    }
}
