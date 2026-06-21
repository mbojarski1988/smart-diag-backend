<?php

declare(strict_types=1);

namespace App\License\Application;

final class LicenseKeyGenerator
{
    public function generate(): string
    {
        return 'lic_' . bin2hex(random_bytes(32));
    }
}
