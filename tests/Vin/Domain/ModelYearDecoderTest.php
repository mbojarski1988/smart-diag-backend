<?php

declare(strict_types=1);

namespace App\Tests\Vin\Domain;

use App\Vin\Domain\ModelYearDecoder;
use PHPUnit\Framework\TestCase;

final class ModelYearDecoderTest extends TestCase
{
    private ModelYearDecoder $decoder;

    protected function setUp(): void
    {
        $this->decoder = new ModelYearDecoder();
    }

    public function testDecodesOlderYearWhenPosition7IsDigit(): void
    {
        // VIN index 6 = '0' (digit), index 9 = 'F' => [1985, 2015] => 1985
        // 1FA6P801FF5300000: [6]='0', [9]='F'
        self::assertSame(1985, $this->decoder->decode('1FA6P801FF5300000'));
    }

    public function testDecodesNewerYearWhenPosition7IsLetter(): void
    {
        // VIN index 6 = 'C' (letter), index 9 = 'F' => [1985, 2015] => 2015
        // 1FA6P8CF0F5300000: [6]='C', [9]='F'
        self::assertSame(2015, $this->decoder->decode('1FA6P8CF0F5300000'));
    }

    public function testDecodesYear2025ForCodeS(): void
    {
        // Position 9 = 'S' => [1995, 2025]; position 7 = 'X' (letter) => 2025
        self::assertSame(2025, $this->decoder->decode('2FA6P8CFXS5300000'));
    }

    public function testDecodesYear2001ForCode1(): void
    {
        // VIN index 6 = '0' (digit), index 9 = '1' => [2001, 2031] => 2001
        // 1FA6P801F15300000: [6]='0', [9]='1'
        self::assertSame(2001, $this->decoder->decode('1FA6P801F15300000'));
    }

    public function testReturnsNullForInvalidLength(): void
    {
        self::assertNull($this->decoder->decode('1FA6P8CF0'));
    }

    public function testReturnsNullForUnknownYearCode(): void
    {
        // Position 9 = 'I' is not a valid VIN year code
        self::assertNull($this->decoder->decode('1FA6P8CF0I5300000'));
    }

    public function testNormalizesLowercaseInput(): void
    {
        self::assertSame(2015, $this->decoder->decode('1fa6p8cfff5300000'));
    }
}
