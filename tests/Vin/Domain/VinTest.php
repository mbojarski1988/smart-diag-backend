<?php

declare(strict_types=1);

namespace App\Tests\Vin\Domain;

use App\Vin\Domain\Vin;
use PHPUnit\Framework\TestCase;

final class VinTest extends TestCase
{
    public function testItCreatesFromValidString(): void
    {
        $vin = Vin::fromString('1FA6P8CF0F5300000');

        self::assertSame('1FA6P8CF0F5300000', $vin->value);
    }

    public function testItNormalizesLowercaseInput(): void
    {
        $vin = Vin::fromString('1fa6p8cf0f5300000');

        self::assertSame('1FA6P8CF0F5300000', $vin->value);
    }

    public function testItTrimsWhitespace(): void
    {
        $vin = Vin::fromString('  1FA6P8CF0F5300000  ');

        self::assertSame('1FA6P8CF0F5300000', $vin->value);
    }

    public function testItThrowsOnTooShortString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Vin::fromString('1FA6P8CF0F530');
    }

    public function testItThrowsOnIllegalCharacter(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        // 'I', 'O', 'Q' are forbidden in VINs
        Vin::fromString('1FA6P8CF0F530000I');
    }

    public function testItThrowsOnEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Vin::fromString('');
    }

    public function testCharAtReturnsCorrectCharacter(): void
    {
        $vin = Vin::fromString('1FA6P8CF0F5300000');

        self::assertSame('1', $vin->charAt(0));
        self::assertSame('F', $vin->charAt(1));
        self::assertSame('0', $vin->charAt(8));
    }

    public function testSubstrReturnsSlice(): void
    {
        $vin = Vin::fromString('1FA6P8CF0F5300000');

        self::assertSame('1FA', $vin->substr(0, 3));
        self::assertSame('300000', $vin->substr(11, 6));
    }

    public function testToStringReturnsValue(): void
    {
        $vin = Vin::fromString('1FA6P8CF0F5300000');

        self::assertSame('1FA6P8CF0F5300000', (string) $vin);
    }
}
