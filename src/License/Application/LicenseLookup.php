<?php

declare(strict_types=1);

namespace App\License\Application;

use App\License\Domain\License;

interface LicenseLookup
{
    public function findByLicenseKey(string $licenseKey): ?License;
}
