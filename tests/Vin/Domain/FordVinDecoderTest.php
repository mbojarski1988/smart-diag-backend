<?php

declare(strict_types=1);

namespace App\Tests\Vin\Domain;

use App\Vin\Domain\EuropeanVinDecoder;
use App\Vin\Domain\FordVinDecoder;
use App\Vin\Domain\ModelYearDecoder;
use App\Vin\Domain\NorthAmericanVinDecoder;
use App\Vin\Domain\VinCheckDigitCalculator;
use App\Vin\Domain\VinFormatValidator;
use App\Vin\Domain\WmiDecoder;
use PHPUnit\Framework\TestCase;

final class FordVinDecoderTest extends TestCase
{
    private FordVinDecoder $decoder;

    protected function setUp(): void
    {
        $calculator = new VinCheckDigitCalculator();
        $modelYearDecoder = new ModelYearDecoder();
        $wmiDecoder = new WmiDecoder();

        $this->decoder = new FordVinDecoder(
            new VinFormatValidator(),
            $wmiDecoder,
            new EuropeanVinDecoder(),
            new NorthAmericanVinDecoder($calculator, $modelYearDecoder, $wmiDecoder),
        );
    }

    public function testItDecodesFordVin(): void
    {
        $result = $this->decoder->decode('1FA6P8CF0F5300000');

        self::assertSame('1FA6P8CF0F5300000', $result->vin);
        self::assertTrue($result->valid);
        self::assertTrue($result->ford);
        self::assertSame('1FA', $result->wmi);
        self::assertSame('Ford USA (Passenger)', $result->manufacturer);
        self::assertSame(2015, $result->modelYear);
        self::assertSame('5', $result->plantCode);
        self::assertSame('300000', $result->serialNumber);
        self::assertSame('North America', $result->region);
        self::assertSame('Series/Line: P8C', $result->model);
        self::assertSame('5.0L V8 (Coyote)', $result->engineCode);
        self::assertSame('Flat Rock, MI (Mustang)', $result->assemblyPlant);
        self::assertSame(2015, $result->productionYear);
        self::assertNull($result->productionMonth);
    }

    public function testItDecodesEuropeanFordVinWithoutCheckDigitValidation(): void
    {
        $result = $this->decoder->decode('WF0MXXGCDM8R43240');

        self::assertSame('WF0MXXGCDM8R43240', $result->vin);
        self::assertTrue($result->valid);
        self::assertTrue($result->ford);
        self::assertSame('WF0', $result->wmi);
        self::assertSame('Ford Werke AG (Europe)', $result->manufacturer);
        self::assertSame(2008, $result->modelYear);
        self::assertSame('C', $result->plantCode);
        self::assertSame('43240', $result->serialNumber);
        self::assertSame('Europe', $result->region);
        self::assertSame('Focus', $result->model);
        self::assertSame('Code: M', $result->bodyStyle);
        self::assertNull($result->engineCode);
        self::assertSame('Saarlouis, Germany', $result->assemblyPlant);
        self::assertSame(2008, $result->productionYear);
        self::assertSame('Unknown', $result->productionMonth);
        self::assertTrue($result->checkDigit->valid);
        self::assertSame('D', $result->checkDigit->actual);
        self::assertNull($result->checkDigit->expected);
        self::assertSame([], $result->warnings);
    }

    public function testItRejectsInvalidFormat(): void
    {
        $result = $this->decoder->decode('INVALID');

        self::assertFalse($result->valid);
        self::assertFalse($result->ford);
        self::assertNotEmpty($result->warnings);
    }
}
