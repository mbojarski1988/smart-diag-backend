<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Shared\Auth\Attribute\RequiresRole;
use App\User\Application\Dto\UserWriteRequest;
use App\User\Application\UserLookup;
use App\User\Application\UserManager;
use App\User\Domain\User;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/users')]
#[RequiresRole('ROLE_ADMIN')]
#[OA\Tag(name: 'User administration')]
final class UserAdminController
{
    public function __construct(
        private readonly UserLookup $users,
        private readonly UserManager $userManager,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'api_admin_users_list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/admin/users',
        operationId: 'adminUsersList',
        summary: 'List all users',
        security: [['BearerAuth' => []]],
        tags: ['User administration'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of users.',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'items',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/UserResponse'),
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthorized.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden — requires ROLE_ADMIN.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function list(): JsonResponse
    {
        return new JsonResponse([
            'items' => array_map(
                static fn (User $u): array => $u->toArray(),
                $this->users->findVisible(),
            ),
        ]);
    }

    #[Route('', name: 'api_admin_users_create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/admin/users',
        operationId: 'adminUsersCreate',
        summary: 'Create a new user',
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UserWriteRequest'),
        ),
        tags: ['User administration'],
        responses: [
            new OA\Response(response: 201, description: 'User created.', content: new OA\JsonContent(ref: '#/components/schemas/UserResponse')),
            new OA\Response(response: 401, description: 'Unauthorized.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden — requires ROLE_ADMIN.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function create(Request $request): JsonResponse
    {
        $dto = UserWriteRequest::fromRequest($request);

        if (is_string($dto)) {
            return new JsonResponse(['error' => $dto], 422);
        }

        $result = $this->userManager->create($dto);

        if (is_string($result)) {
            return new JsonResponse(['error' => $result], 422);
        }

        $this->entityManager->persist($result);
        $this->entityManager->flush();

        return new JsonResponse($result->toArray(), 201);
    }

    #[Route('/{id}', name: 'api_admin_users_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[OA\Get(
        path: '/api/admin/users/{id}',
        operationId: 'adminUsersShow',
        summary: 'Get a single user',
        security: [['BearerAuth' => []]],
        tags: ['User administration'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'User data.', content: new OA\JsonContent(ref: '#/components/schemas/UserResponse')),
            new OA\Response(response: 401, description: 'Unauthorized.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden — requires ROLE_ADMIN.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'User not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function show(int $id): JsonResponse
    {
        $user = $this->users->findById($id);

        if (!$user instanceof User || $user->getDeletedAt() !== null) {
            return new JsonResponse(['error' => 'user_not_found'], 404);
        }

        return new JsonResponse($user->toArray());
    }

    #[Route('/{id}', name: 'api_admin_users_update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    #[OA\Patch(
        path: '/api/admin/users/{id}',
        operationId: 'adminUsersUpdate',
        summary: 'Update a user',
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UserWriteRequest'),
        ),
        tags: ['User administration'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Updated user data.', content: new OA\JsonContent(ref: '#/components/schemas/UserResponse')),
            new OA\Response(response: 401, description: 'Unauthorized.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden — requires ROLE_ADMIN.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'User not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->users->findById($id);

        if (!$user instanceof User || $user->getDeletedAt() !== null) {
            return new JsonResponse(['error' => 'user_not_found'], 404);
        }

        $dto = UserWriteRequest::fromPatchRequest($request, $user);

        if (is_string($dto)) {
            return new JsonResponse(['error' => $dto], 422);
        }

        $this->userManager->update($user, $dto);
        $this->entityManager->flush();

        return new JsonResponse($user->toArray());
    }

    #[Route(
        '/{id}/reset-password',
        name: 'api_admin_users_reset_password',
        requirements: ['id' => '\d+'],
        methods: ['POST'],
    )]
    #[OA\Post(
        path: '/api/admin/users/{id}/reset-password',
        operationId: 'adminUsersResetPassword',
        summary: 'Reset a user\'s password',
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['password'],
                properties: [
                    new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'newSecret123'),
                ],
                type: 'object',
            ),
        ),
        tags: ['User administration'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Password changed, updated user data.', content: new OA\JsonContent(ref: '#/components/schemas/UserResponse')),
            new OA\Response(response: 401, description: 'Unauthorized.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden — requires ROLE_ADMIN.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'User not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function resetPassword(int $id, Request $request): JsonResponse
    {
        $user = $this->users->findById($id);

        if (!$user instanceof User || $user->getDeletedAt() !== null) {
            return new JsonResponse(['error' => 'user_not_found'], 404);
        }

        try {
            /** @var array<string, mixed> $data */
            $data = json_decode((string) $request->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return new JsonResponse(['error' => 'invalid_json'], 422);
        }

        $rawPassword = $data['password'] ?? '';
        $password = is_string($rawPassword) ? $rawPassword : '';

        if (strlen($password) < 8) {
            return new JsonResponse(['error' => 'password_too_short'], 422);
        }

        $this->userManager->resetPassword($user, $password);
        $this->entityManager->flush();

        return new JsonResponse($user->toArray());
    }

    #[Route('/{id}', name: 'api_admin_users_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/admin/users/{id}',
        operationId: 'adminUsersDelete',
        summary: 'Soft-delete a user',
        security: [['BearerAuth' => []]],
        tags: ['User administration'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Deleted user data.', content: new OA\JsonContent(ref: '#/components/schemas/UserResponse')),
            new OA\Response(response: 401, description: 'Unauthorized.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden — requires ROLE_ADMIN.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'User not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function delete(int $id): JsonResponse
    {
        $user = $this->users->findById($id);

        if (!$user instanceof User || $user->getDeletedAt() !== null) {
            return new JsonResponse(['error' => 'user_not_found'], 404);
        }

        $user->softDelete();
        $this->entityManager->flush();

        return new JsonResponse($user->toArray());
    }
}
