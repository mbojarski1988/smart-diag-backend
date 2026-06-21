<?php

declare(strict_types=1);

namespace App\Tests\License\Application;

use App\License\Application\LicenseValidator;
use App\License\Domain\License;
use PHPUnit\Framework\TestCase;

final class LicenseValidatorTest extends TestCase
{
    public function testItAcceptsActiveNotExpiredLicense(): void
    {
        $license = $this->license(validUntil: '+1 day');
        $validator = new LicenseValidator(new InMemoryLicenseLookup($license));

        $result = $validator->validate($license->getLicenseKey());

        self::assertTrue($result->valid);
        self::assertSame($license, $result->license);
    }

    public function testItRejectsMissingLicenseKey(): void
    {
        $validator = new LicenseValidator(new InMemoryLicenseLookup());

        $result = $validator->validate('');

        self::assertFalse($result->valid);
        self::assertSame(401, $result->statusCode);
        self::assertSame('missing_license', $result->errorCode);
    }

    public function testItRejectsInactiveLicense(): void
    {
        $license = $this->license(validUntil: '+1 day');
        $license->deactivate();
        $validator = new LicenseValidator(new InMemoryLicenseLookup($license));

        $result = $validator->validate($license->getLicenseKey());

        self::assertFalse($result->valid);
        self::assertSame(403, $result->statusCode);
        self::assertSame('inactive_license', $result->errorCode);
    }

    public function testItRejectsExpiredLicense(): void
    {
        $license = $this->license(validUntil: '-1 day');
        $validator = new LicenseValidator(new InMemoryLicenseLookup($license));

        $result = $validator->validate($license->getLicenseKey());

        self::assertFalse($result->valid);
        self::assertSame(403, $result->statusCode);
        self::assertSame('expired_license', $result->errorCode);
    }

    public function testItRejectsDeletedLicense(): void
    {
        $license = $this->license(validUntil: '+1 day');
        $license->softDelete();
        $validator = new LicenseValidator(new InMemoryLicenseLookup($license));

        $result = $validator->validate($license->getLicenseKey());

        self::assertFalse($result->valid);
        self::assertSame(403, $result->statusCode);
        self::assertSame('invalid_license', $result->errorCode);
    }

    private function license(string $validUntil): License
    {
        return new License(
            licenseKey: 'lic_test',
            clientName: 'Test Client',
            clientEmail: 'client@example.com',
            note: null,
            validUntil: new \DateTimeImmutable($validUntil),
        );
    }
}
