<?php

declare(strict_types=1);

namespace App\Tests\Vin\Domain;

use App\Vin\Domain\EuropeanVinDecoder;
use App\Vin\Domain\Vin;
use PHPUnit\Framework\TestCase;

final class EuropeanVinDecoderTest extends TestCase
{
    private EuropeanVinDecoder $decoder;

    protected function setUp(): void
    {
        $this->decoder = new EuropeanVinDecoder();
    }

    public function testItDecodesKnownPlantAndModel(): void
    {
        // WF0MXXGCDM8R43240: plant@7=C (Saarlouis), model@8=D (Focus), year@10='8'=2008
        $result = $this->decoder->decode(Vin::fromString('WF0MXXGCDM8R43240'), 'WF0');

        self::assertSame('Europe', $result->region);
        self::assertSame('Focus', $result->model);
        self::assertSame('Saarlouis, Germany', $result->assemblyPlant);
        self::assertSame(2008, $result->productionYear);
        self::assertSame('Unknown', $result->productionMonth);
        self::assertTrue($result->checkDigit->valid);
        self::assertNull($result->checkDigit->expected);
        self::assertSame('Ford Werke AG (Europe)', $result->manufacturer);
    }

    public function testItDecodesMonthAfter2010(): void
    {
        // WF0XXXGCXXLA00001: 'L'@10=2020, 'A'@11
        // cycleIndex=(2020-2010)%4=2; March=['S','D','A','M'], index 2='A' -> March
        $result = $this->decoder->decode(Vin::fromString('WF0XXXGCXXLA00001'), 'WF0');

        self::assertSame(2020, $result->productionYear);
        self::assertSame('March', $result->productionMonth);
    }

    public function testItHandlesUnknownPlantCode(): void
    {
        // Plant code at position 7 = 'Z' (not in EU_PLANTS)
        $result = $this->decoder->decode(Vin::fromString('WF0MXXGZDM8R43240'), 'WF0');

        self::assertIsString($result->assemblyPlant);
        self::assertStringContainsString('Plant code: Z', $result->assemblyPlant);
    }

    public function testItHandlesUnknownModelCode(): void
    {
        // Model code at position 8 = 'Z' (not in EU_MODELS)
        $result = $this->decoder->decode(Vin::fromString('WF0MXXGCZM8R43240'), 'WF0');

        self::assertIsString($result->model);
        self::assertStringContainsString('Unknown', $result->model);
        self::assertStringContainsString('Z', $result->model);
    }
}
