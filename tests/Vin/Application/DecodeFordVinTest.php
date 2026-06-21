<?php

declare(strict_types=1);

namespace App\Tests\Vin\Application;

use App\Vin\Application\DecodeFordVin;
use App\Vin\Domain\EuropeanVinDecoder;
use App\Vin\Domain\FordVinDecoder;
use App\Vin\Domain\ModelYearDecoder;
use App\Vin\Domain\NorthAmericanVinDecoder;
use App\Vin\Domain\VinCheckDigitCalculator;
use App\Vin\Domain\VinFormatValidator;
use App\Vin\Domain\WmiDecoder;
use App\Vin\Infrastructure\Nhtsa\NhtsaVinClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class DecodeFordVinTest extends TestCase
{
    public function testItCachesExternalVinDecodeResult(): void
    {
        $requestCount = 0;
        $httpClient = new MockHttpClient(
            function () use (&$requestCount): MockResponse {
                ++$requestCount;

                return new MockResponse(json_encode([
                    'Results' => [
                        [
                            'Make' => 'FORD',
                            'Model' => 'Mustang',
                        ],
                    ],
                ], JSON_THROW_ON_ERROR));
            },
        );

        $calculator = new VinCheckDigitCalculator();
        $modelYearDecoder = new ModelYearDecoder();
        $wmiDecoder = new WmiDecoder();

        $decodeFordVin = new DecodeFordVin(
            new FordVinDecoder(
                new VinFormatValidator(),
                $wmiDecoder,
                new EuropeanVinDecoder(),
                new NorthAmericanVinDecoder($calculator, $modelYearDecoder, $wmiDecoder),
            ),
            new NhtsaVinClient($httpClient),
            new ArrayAdapter(),
        );

        $first = $decodeFordVin('1FA6P8CF0F5300000', withExternal: true);
        $second = $decodeFordVin('1FA6P8CF0F5300000', withExternal: true);

        self::assertSame(1, $requestCount);
        self::assertSame($first->external, $second->external);

        $results = $second->external['Results'] ?? null;
        self::assertIsArray($results);
        /** @var array<string, mixed> $firstResult */
        $firstResult = $results[0];
        self::assertSame('FORD', $firstResult['Make']);
    }
}
