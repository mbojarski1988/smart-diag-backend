<?php

declare(strict_types=1);

namespace App\Tests\License\Domain;

use App\License\Domain\License;
use PHPUnit\Framework\TestCase;

final class LicenseTest extends TestCase
{
    public function testNewLicenseHasCorrectDefaults(): void
    {
        $validUntil = new \DateTimeImmutable('+1 year');
        $license = new License('lic_abc', 'ACME', 'acme@example.com', null, $validUntil);

        self::assertNull($license->getId());
        self::assertSame('lic_abc', $license->getLicenseKey());
        self::assertSame('ACME', $license->getClientName());
        self::assertSame('acme@example.com', $license->getClientEmail());
        self::assertNull($license->getNote());
        self::assertTrue($license->isActive());
        self::assertNull($license->getDeactivatedAt());
        self::assertNull($license->getDeletedAt());
    }

    public function testIsUsableAtReturnsTrueForActiveNonExpiredLicense(): void
    {
        $license = $this->license('+1 day');

        self::assertTrue($license->isUsableAt(new \DateTimeImmutable()));
    }

    public function testIsUsableAtReturnsFalseForExpiredLicense(): void
    {
        $license = $this->license('-1 day');

        self::assertFalse($license->isUsableAt(new \DateTimeImmutable()));
    }

    public function testIsUsableAtReturnsFalseForInactiveLicense(): void
    {
        $license = $this->license('+1 day');
        $license->deactivate();

        self::assertFalse($license->isUsableAt(new \DateTimeImmutable()));
    }

    public function testIsUsableAtReturnsFalseForDeletedLicense(): void
    {
        $license = $this->license('+1 day');
        $license->softDelete();

        self::assertFalse($license->isUsableAt(new \DateTimeImmutable()));
    }

    public function testDeactivateSetsDeactivatedAtAndMarksInactive(): void
    {
        $license = $this->license('+1 day');
        $before = new \DateTimeImmutable();
        $license->deactivate();

        self::assertFalse($license->isActive());
        self::assertNotNull($license->getDeactivatedAt());
        self::assertGreaterThanOrEqual($before, $license->getDeactivatedAt());
    }

    public function testSoftDeleteSetsDeletedAtAndDeactivates(): void
    {
        $license = $this->license('+1 day');
        $before = new \DateTimeImmutable();
        $license->softDelete();

        self::assertFalse($license->isActive());
        self::assertNotNull($license->getDeletedAt());
        self::assertGreaterThanOrEqual($before, $license->getDeletedAt());
    }

    public function testUpdateChangesClientDetails(): void
    {
        $license = $this->license('+1 day');
        $newValidUntil = new \DateTimeImmutable('+2 years');

        $license->update('New Name', 'new@example.com', 'note', $newValidUntil, true);

        self::assertSame('New Name', $license->getClientName());
        self::assertSame('new@example.com', $license->getClientEmail());
        self::assertSame('note', $license->getNote());
        self::assertSame($newValidUntil, $license->getValidUntil());
    }

    public function testUpdateSetsDeactivatedAtWhenDeactivating(): void
    {
        $license = $this->license('+1 day');
        $license->update('Name', 'e@example.com', null, new \DateTimeImmutable('+1 day'), false);

        self::assertFalse($license->isActive());
        self::assertNotNull($license->getDeactivatedAt());
    }

    public function testUpdateClearsDeactivatedAtWhenReactivating(): void
    {
        $license = $this->license('+1 day');
        $license->deactivate();
        $license->update('Name', 'e@example.com', null, new \DateTimeImmutable('+1 day'), true);

        self::assertTrue($license->isActive());
        self::assertNull($license->getDeactivatedAt());
    }

    public function testToArrayOmitsLicenseKeyByDefault(): void
    {
        $license = $this->license('+1 day');
        $data = $license->toArray();

        self::assertArrayNotHasKey('licenseKey', $data);
        self::assertArrayHasKey('clientName', $data);
    }

    public function testToArrayIncludesLicenseKeyWhenRequested(): void
    {
        $license = $this->license('+1 day');
        $data = $license->toArray(includeKey: true);

        self::assertArrayHasKey('licenseKey', $data);
        self::assertSame('lic_test', $data['licenseKey']);
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
