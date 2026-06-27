<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\License\Application\Dto\LicenseWriteRequest;
use App\License\Application\LicenseKeyGenerator;
use App\License\Domain\License;
use App\License\Infrastructure\LicenseRepository;
use App\Shared\Auth\Attribute\RequiresRole;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/licenses')]
#[RequiresRole('ROLE_ADMIN')]
final class LicenseAdminController extends AbstractController
{
    /**
     * @throws \Exception
     */
    #[Route('', name: 'api_admin_licenses_create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/admin/licenses',
        operationId: 'createLicense',
        summary: 'Create a license',
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/LicenseWrite'),
        ),
        tags: ['License administration'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Created license including generated licenseKey.',
                content: new OA\JsonContent(ref: '#/components/schemas/LicenseWithKey'),
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid request body.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        LicenseKeyGenerator $licenseKeyGenerator,
    ): JsonResponse {
        $dto = LicenseWriteRequest::fromRequest($request);

        if (is_string($dto)) {
            return $this->json(['error' => $dto], 400);
        }

        $license = new License(
            licenseKey: $licenseKeyGenerator->generate(),
            clientName: $dto->clientName,
            clientEmail: $dto->clientEmail,
            note: $dto->note,
            validUntil: $dto->validUntil,
        );

        $entityManager->persist($license);
        $entityManager->flush();

        return $this->json($license->toArray(includeKey: true), 201);
    }

    #[Route('', name: 'api_admin_licenses_list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/admin/licenses',
        operationId: 'listLicenses',
        summary: 'List licenses',
        security: [['BearerAuth' => []]],
        tags: ['License administration'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'License list.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'items',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/License'),
                        ),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function list(LicenseRepository $licenses): JsonResponse
    {
        return $this->json([
            'items' => array_map(
                static fn (License $license): array => $license->toArray(),
                $licenses->findVisible(),
            ),
        ]);
    }

    #[Route('/{id}', name: 'api_admin_licenses_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[OA\Get(
        path: '/api/admin/licenses/{id}',
        operationId: 'getLicense',
        tags: ['License administration'],
        summary: 'Get a license',
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'License details.',
                content: new OA\JsonContent(ref: '#/components/schemas/LicenseWithKey'),
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 404,
                description: 'License not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function show(int $id, LicenseRepository $licenses): JsonResponse
    {
        $license = $licenses->find($id);

        if (!$license instanceof License || $license->getDeletedAt() !== null) {
            return $this->json(['error' => 'license_not_found'], 404);
        }

        return $this->json($license->toArray(includeKey: true));
    }

    #[Route('/{id}', name: 'api_admin_licenses_update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    #[OA\Patch(
        path: '/api/admin/licenses/{id}',
        operationId: 'updateLicense',
        tags: ['License administration'],
        summary: 'Update a license',
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/LicensePatch'),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Updated license.',
                content: new OA\JsonContent(ref: '#/components/schemas/LicenseWithKey'),
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid request body.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 404,
                description: 'License not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function update(
        int $id,
        Request $request,
        LicenseRepository $licenses,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $license = $licenses->find($id);

        if (!$license instanceof License || $license->getDeletedAt() !== null) {
            return $this->json(['error' => 'license_not_found'], 404);
        }

        $dto = LicenseWriteRequest::fromPatchRequest($request, $license);

        if (is_string($dto)) {
            return $this->json(['error' => $dto], 400);
        }

        $license->update(
            clientName: $dto->clientName,
            clientEmail: $dto->clientEmail,
            note: $dto->note,
            validUntil: $dto->validUntil,
            active: (bool) $dto->active,
        );

        $entityManager->flush();

        return $this->json($license->toArray(includeKey: true));
    }

    #[Route(
        '/{id}/deactivate',
        name: 'api_admin_licenses_deactivate',
        requirements: ['id' => '\d+'],
        methods: ['POST'],
    )]
    #[OA\Post(
        path: '/api/admin/licenses/{id}/deactivate',
        operationId: 'deactivateLicense',
        tags: ['License administration'],
        summary: 'Deactivate a license',
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Deactivated license.',
                content: new OA\JsonContent(ref: '#/components/schemas/LicenseWithKey'),
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 404,
                description: 'License not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function deactivate(
        int $id,
        LicenseRepository $licenses,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $license = $licenses->find($id);

        if (!$license instanceof License || $license->getDeletedAt() !== null) {
            return $this->json(['error' => 'license_not_found'], 404);
        }

        $license->deactivate();
        $entityManager->flush();

        return $this->json($license->toArray(includeKey: true));
    }

    #[Route('/{id}', name: 'api_admin_licenses_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/admin/licenses/{id}',
        operationId: 'deleteLicense',
        tags: ['License administration'],
        summary: 'Soft delete a license',
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Soft-deleted license.',
                content: new OA\JsonContent(ref: '#/components/schemas/LicenseWithKey'),
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 404,
                description: 'License not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function delete(
        int $id,
        LicenseRepository $licenses,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $license = $licenses->find($id);

        if (!$license instanceof License || $license->getDeletedAt() !== null) {
            return $this->json(['error' => 'license_not_found'], 404);
        }

        $license->softDelete();
        $entityManager->flush();

        return $this->json($license->toArray(includeKey: true));
    }
}
