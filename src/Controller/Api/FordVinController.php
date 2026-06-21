<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Shared\Auth\Attribute\RequiresLicense;
use App\Vin\Application\DecodeFordVin;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class FordVinController extends AbstractController
{
    #[Route('/api/vin/ford/{vin}', name: 'api_vin_ford_decode', methods: ['GET'])]
    #[RequiresLicense]
    #[OA\Get(
        path: '/api/vin/ford/{vin}',
        operationId: 'decodeFordVin',
        summary: 'Decode a Ford VIN',
        security: [['LicenseKey' => []]],
        tags: ['Client services'],
        parameters: [
            new OA\Parameter(
                name: 'vin',
                description: '17-character VIN without I, O, or Q.',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', minLength: 17, maxLength: 17, example: '1FA6P8CF0F5300000'),
            ),
            new OA\Parameter(
                name: 'external',
                description: 'Set to true or 1 to enrich the response with NHTSA vPIC data.',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean', default: false),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'VIN decode result.',
                content: new OA\JsonContent(ref: '#/components/schemas/VinDecodeResponse'),
            ),
            new OA\Response(
                response: 401,
                description: 'Missing license key.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 403,
                description: 'Invalid, inactive, or expired license.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function decode(string $vin, Request $request, DecodeFordVin $decodeFordVin): JsonResponse
    {
        $result = $decodeFordVin($vin, $request->query->getBoolean('external', false));

        return $this->json($result->toArray());
    }

    #[Route('/health', name: 'health', methods: ['GET'])]
    #[OA\Get(
        path: '/health',
        operationId: 'healthCheck',
        summary: 'Health check',
        tags: ['Client services'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Application is running.',
                content: new OA\JsonContent(
                    required: ['status'],
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'ok'),
                    ],
                    type: 'object',
                ),
            ),
        ],
    )]
    public function health(): JsonResponse
    {
        return $this->json(['status' => 'ok']);
    }
}
