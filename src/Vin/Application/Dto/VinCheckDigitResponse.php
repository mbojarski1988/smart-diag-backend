<?php

declare(strict_types=1);

namespace App\Vin\Application\Dto;

final readonly class VinCheckDigitResponse
{
    public function __construct(
        public bool $valid,
        public ?string $actual,
        public ?string $expected,
    ) {
    }

    /**
     * @param array{valid: bool, actual: string|null, expected: string|null} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            valid: $data['valid'],
            actual: $data['actual'],
            expected: $data['expected'],
        );
    }

    /**
     * @return array{valid: bool, actual: string|null, expected: string|null}
     */
    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'actual' => $this->actual,
            'expected' => $this->expected,
        ];
    }
}
