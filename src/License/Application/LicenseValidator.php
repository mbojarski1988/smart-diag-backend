<?php

declare(strict_types=1);

namespace App\License\Application;

final readonly class LicenseValidator
{
    public function __construct(private LicenseLookup $licenses)
    {
    }

    public function validate(?string $licenseKey): LicenseValidationResult
    {
        $licenseKey = trim((string) $licenseKey);

        if ($licenseKey === '') {
            return LicenseValidationResult::missing();
        }

        $license = $this->licenses->findByLicenseKey($licenseKey);

        if ($license === null || $license->getDeletedAt() !== null) {
            return LicenseValidationResult::invalid('invalid_license');
        }

        if (!$license->isActive()) {
            return LicenseValidationResult::invalid('inactive_license');
        }

        if ($license->getValidUntil() < new \DateTimeImmutable()) {
            return LicenseValidationResult::invalid('expired_license');
        }

        return LicenseValidationResult::valid($license);
    }
}
