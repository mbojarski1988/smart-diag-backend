<?php

declare(strict_types=1);

namespace App\License\Application;

use App\License\Domain\License;

final readonly class LicenseValidationResult
{
    private function __construct(
        public bool $valid,
        public int $statusCode,
        public string $errorCode,
        public ?License $license = null,
    ) {
    }

    public static function valid(License $license): self
    {
        return new self(true, 200, '', $license);
    }

    public static function missing(): self
    {
        return new self(false, 401, 'missing_license');
    }

    public static function invalid(string $errorCode): self
    {
        return new self(false, 403, $errorCode);
    }
}
