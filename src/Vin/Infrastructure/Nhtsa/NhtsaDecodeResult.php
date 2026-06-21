<?php

declare(strict_types=1);

namespace App\Vin\Infrastructure\Nhtsa;

final readonly class NhtsaDecodeResult
{
    /**
     * @param list<array<string, mixed>> $results
     * @param array<string, mixed>       $raw
     */
    public function __construct(
        public array $results,
        public array $raw,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        /** @var list<array<string, mixed>> $results */
        $results = is_array($data['Results'] ?? null) ? $data['Results'] : [];

        return new self(
            results: $results,
            raw: $data,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->raw;
    }
}
