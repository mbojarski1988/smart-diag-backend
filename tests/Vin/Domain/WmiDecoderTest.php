<?php

declare(strict_types=1);

namespace App\Tests\Vin\Domain;

use App\Vin\Domain\WmiDecoder;
use PHPUnit\Framework\TestCase;

final class WmiDecoderTest extends TestCase
{
    private WmiDecoder $decoder;

    protected function setUp(): void
    {
        $this->decoder = new WmiDecoder();
    }

    public function testGetWmiReturnsFirst3Characters(): void
    {
        self::assertSame('1FA', $this->decoder->getWmi('1FA6P8CF0F5300000'));
    }

    public function testGetWmiNormalizesInput(): void
    {
        self::assertSame('1FA', $this->decoder->getWmi('  1fa6p8cf0f5300000  '));
    }

    public function testGetWmiReturnsNullForTooShortString(): void
    {
        self::assertNull($this->decoder->getWmi('1F'));
    }

    public function testDecodeManufacturerReturnsKnownFordUsaPassenger(): void
    {
        self::assertSame('Ford USA (Passenger)', $this->decoder->decodeManufacturer('1FA6P8CF0F5300000'));
    }

    public function testDecodeManufacturerReturnsFordEurope(): void
    {
        self::assertSame('Ford Europe', $this->decoder->decodeManufacturer('WF0MXXGCDM8R43240'));
    }

    public function testDecodeManufacturerReturnsNullForUnknownWmi(): void
    {
        self::assertNull($this->decoder->decodeManufacturer('ZZZ000000000000000'));
    }

    public function testRequiresCheckDigitForNorthAmericanVin(): void
    {
        self::assertTrue($this->decoder->requiresCheckDigit('1FA6P8CF0F5300000'));
        self::assertTrue($this->decoder->requiresCheckDigit('2FA6P8CF0F5300000'));
        self::assertTrue($this->decoder->requiresCheckDigit('3FA6P8CF0F5300000'));
        self::assertTrue($this->decoder->requiresCheckDigit('4FA6P8CF0F5300000'));
        self::assertTrue($this->decoder->requiresCheckDigit('5FA6P8CF0F5300000'));
    }

    public function testDoesNotRequireCheckDigitForEuropeanVin(): void
    {
        self::assertFalse($this->decoder->requiresCheckDigit('WF0MXXGCDM8R43240'));
    }

    public function testDoesNotRequireCheckDigitForEmptyString(): void
    {
        self::assertFalse($this->decoder->requiresCheckDigit(''));
    }
}
