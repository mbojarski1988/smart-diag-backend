<?php

declare(strict_types=1);

namespace App\Tests\Vin\Domain;

use App\Vin\Domain\VinCheckDigitCalculator;
use PHPUnit\Framework\TestCase;

final class VinCheckDigitCalculatorTest extends TestCase
{
    public function testItCalculatesValidCheckDigit(): void
    {
        $calculator = new VinCheckDigitCalculator();

        self::assertSame('0', $calculator->calculate('1FA6P8CF0F5300000'));
        self::assertTrue($calculator->isValid('1FA6P8CF0F5300000'));
    }

    public function testItRejectsInvalidLength(): void
    {
        $calculator = new VinCheckDigitCalculator();

        self::assertNull($calculator->calculate('123'));
        self::assertFalse($calculator->isValid('123'));
    }
}
