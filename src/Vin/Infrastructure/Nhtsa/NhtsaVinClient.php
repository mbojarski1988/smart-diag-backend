<?php

declare(strict_types=1);

namespace App\Vin\Infrastructure\Nhtsa;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class NhtsaVinClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
    }

    public function decode(string $vin, ?int $modelYear = null): NhtsaDecodeResult
    {
        $query = ['format' => 'json'];

        if ($modelYear !== null) {
            $query['modelyear'] = (string) $modelYear;
        }

        $response = $this->httpClient->request(
            'GET',
            sprintf(
                'https://vpic.nhtsa.dot.gov/api/vehicles/DecodeVinValuesExtended/%s',
                urlencode(strtoupper(trim($vin))),
            ),
            [
                'query' => $query,
                'timeout' => 10,
            ],
        );

        return NhtsaDecodeResult::fromArray($response->toArray(false));
    }
}
