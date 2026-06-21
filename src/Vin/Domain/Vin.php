<?php

declare(strict_types=1);

namespace App\Vin\Domain;

final readonly class Vin
{
    private function __construct(public string $value)
    {
    }

    public static function fromString(string $raw): self
    {
        $normalized = strtoupper(trim($raw));

        if (preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $normalized) !== 1) {
            throw new \InvalidArgumentException(sprintf('Invalid VIN format: "%s"', $raw));
        }

        return new self($normalized);
    }

    public function charAt(int $position): string
    {
        return $this->value[$position];
    }

    public function substr(int $start, int $length): string
    {
        return substr($this->value, $start, $length);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
