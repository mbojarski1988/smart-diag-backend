<?php

declare(strict_types=1);

namespace App\Vin\Application;

use App\Vin\Application\Dto\VinDecodeResponse;
use App\Vin\Domain\FordVinDecoder;
use App\Vin\Infrastructure\Nhtsa\NhtsaVinClient;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final readonly class DecodeFordVin
{
    private const CACHE_TTL_SECONDS = 86400;

    public function __construct(
        private FordVinDecoder $fordVinDecoder,
        private NhtsaVinClient $nhtsaVinClient,
        private CacheInterface $cache,
    ) {
    }

    public function __invoke(string $vin, bool $withExternal = false): VinDecodeResponse
    {
        $local = $this->decodeLocal($vin);

        if (!$withExternal || !$local->ford) {
            return $local;
        }

        try {
            $external = $this->cache->get(
                $this->externalCacheKey($local),
                function (ItemInterface $item) use ($local): array {
                    $item->expiresAfter(self::CACHE_TTL_SECONDS);

                    return $this->nhtsaVinClient->decode($local->vin, $local->modelYear)->toArray();
                },
            );
        } catch (TransportExceptionInterface | HttpExceptionInterface | DecodingExceptionInterface) {
            return $this->withExternalData(
                $local,
                [],
                [...$local->warnings, 'Could not fetch data from NHTSA.'],
            );
        }

        return $this->withExternalData($local, $external, $local->warnings);
    }

    private function decodeLocal(string $vin): VinDecodeResponse
    {
        /** @var array{vin: string, valid: bool, ford: bool, wmi: string|null, manufacturer: string|null, modelYear: int|null, plantCode: string|null, serialNumber: string|null, region: string|null, model: string|null, bodyStyle: string|null, engineCode: string|null, assemblyPlant: string|null, productionYear: int|null, productionMonth: string|null, errorMessage: string|null, checkDigit: array{valid: bool, actual: string|null, expected: string|null}, warnings: list<string>, external: array<string, mixed>} $data */
        $data = $this->cache->get(
            $this->localCacheKey($vin),
            function (ItemInterface $item) use ($vin): array {
                $item->expiresAfter(self::CACHE_TTL_SECONDS);

                return $this->fordVinDecoder->decode($vin)->toArray();
            },
        );

        return VinDecodeResponse::fromArray($data);
    }

    /**
     * @param array<string, mixed> $external
     * @param list<string> $warnings
     */
    private function withExternalData(VinDecodeResponse $local, array $external, array $warnings): VinDecodeResponse
    {
        return new VinDecodeResponse(
            vin: $local->vin,
            valid: $local->valid,
            ford: $local->ford,
            wmi: $local->wmi,
            manufacturer: $local->manufacturer,
            modelYear: $local->modelYear,
            plantCode: $local->plantCode,
            serialNumber: $local->serialNumber,
            checkDigit: $local->checkDigit,
            warnings: $warnings,
            external: $external,
            region: $local->region,
            model: $local->model,
            bodyStyle: $local->bodyStyle,
            engineCode: $local->engineCode,
            assemblyPlant: $local->assemblyPlant,
            productionYear: $local->productionYear,
            productionMonth: $local->productionMonth,
            errorMessage: $local->errorMessage,
        );
    }

    private function localCacheKey(string $vin): string
    {
        return sprintf('vin.decode.local.%s', hash('xxh128', strtoupper(trim($vin))));
    }

    private function externalCacheKey(VinDecodeResponse $local): string
    {
        return sprintf(
            'vin.decode.external.%s.%s',
            hash('xxh128', $local->vin),
            $local->modelYear ?? 'unknown',
        );
    }
}
