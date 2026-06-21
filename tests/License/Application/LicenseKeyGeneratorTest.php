<?php

declare(strict_types=1);

namespace App\Tests\License\Application;

use App\License\Application\LicenseKeyGenerator;
use PHPUnit\Framework\TestCase;

final class LicenseKeyGeneratorTest extends TestCase
{
    public function testGeneratedKeyStartsWithLicPrefix(): void
    {
        $key = (new LicenseKeyGenerator())->generate();

        self::assertStringStartsWith('lic_', $key);
    }

    public function testGeneratedKeyHasCorrectLength(): void
    {
        // 'lic_' (4) + hex of 32 bytes (64) = 68 chars
        $key = (new LicenseKeyGenerator())->generate();

        self::assertSame(68, strlen($key));
    }

    public function testGeneratedKeyIsHexAfterPrefix(): void
    {
        $key = (new LicenseKeyGenerator())->generate();
        $hex = substr($key, 4);

        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $hex);
    }

    public function testEachCallGeneratesUniqueKey(): void
    {
        $generator = new LicenseKeyGenerator();

        self::assertNotSame($generator->generate(), $generator->generate());
    }
}
