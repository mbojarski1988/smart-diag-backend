<?php

declare(strict_types=1);

namespace App\Tests\License\Application;

use App\License\Application\LicenseLookup;
use App\License\Domain\License;

final readonly class InMemoryLicenseLookup implements LicenseLookup
{
    public function __construct(private ?License $license = null)
    {
    }

    public function findByLicenseKey(string $licenseKey): ?License
    {
        if ($this->license === null || $this->license->getLicenseKey() !== $licenseKey) {
            return null;
        }

        return $this->license;
    }
}
