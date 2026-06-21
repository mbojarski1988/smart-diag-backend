<?php

declare(strict_types=1);

namespace App\Tests\Vin\Domain;

use App\Vin\Domain\ModelYearDecoder;
use App\Vin\Domain\NorthAmericanVinDecoder;
use App\Vin\Domain\Vin;
use App\Vin\Domain\VinCheckDigitCalculator;
use App\Vin\Domain\WmiDecoder;
use PHPUnit\Framework\TestCase;

final class NorthAmericanVinDecoderTest extends TestCase
{
    private NorthAmericanVinDecoder $decoder;

    protected function setUp(): void
    {
        $this->decoder = new NorthAmericanVinDecoder(
            new VinCheckDigitCalculator(),
            new ModelYearDecoder(),
            new WmiDecoder(),
        );
    }

    public function testItDecodesValidFordVin(): void
    {
        $result = $this->decoder->decode(Vin::fromString('1FA6P8CF0F5300000'), '1FA');

        self::assertTrue($result->valid);
        self::assertTrue($result->ford);
        self::assertSame('North America', $result->region);
        self::assertSame('5.0L V8 (Coyote)', $result->engineCode);
        self::assertSame('Flat Rock, MI (Mustang)', $result->assemblyPlant);
        self::assertSame(2015, $result->productionYear);
        self::assertTrue($result->checkDigit->valid);
        self::assertSame('0', $result->checkDigit->actual);
        self::assertSame('0', $result->checkDigit->expected);
        self::assertSame([], $result->warnings);
    }

    public function testItDetectsInvalidCheckDigit(): void
    {
        // Flip the check digit at position 8 from '0' to '1'
        $result = $this->decoder->decode(Vin::fromString('1FA6P8CF1F5300000'), '1FA');

        self::assertFalse($result->valid);
        self::assertFalse($result->checkDigit->valid);
        self::assertSame('1', $result->checkDigit->actual);
        self::assertSame('0', $result->checkDigit->expected);
        self::assertNotEmpty($result->warnings);
    }

    public function testItDecodesYear2025(): void
    {
        // Build a VIN with 'S' at position 9 (index 9) to represent model year 2025.
        // 1FA6P8CF0S5300000 — recalculate check digit: use a known-valid structure
        // We'll use the decoder with a non-ford WMI to verify just the year mapping.
        // VIN: 2FA6P8CF0S5300000 — '2' makes it NA (requiresCheckDigit), WMI 2FA = Ford Canada
        // We don't care if check digit is wrong; we only test the year field
        $result = $this->decoder->decode(Vin::fromString('2FA6P8CF0S5300000'), '2FA');

        self::assertSame(2025, $result->modelYear);
        self::assertSame(2025, $result->productionYear);
    }

    public function testItHandlesUnknownWmi(): void
    {
        // '1ZZ...' — first char is '1' so requiresCheckDigit=true, but WMI '1ZZ' is not Ford
        $result = $this->decoder->decode(Vin::fromString('1ZZ6P8CF0F5300000'), null);

        self::assertFalse($result->ford);
        self::assertNull($result->manufacturer);
        self::assertNotEmpty($result->warnings);
        self::assertStringContainsString('Ford WMI', $result->warnings[0]);
    }
}
