<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Shared\Auth\Attribute\RequiresRole;
use App\User\Application\Dto\UserWriteRequest;
use App\User\Application\UserLookup;
use App\User\Application\UserManager;
use App\User\Domain\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/users')]
#[RequiresRole('ROLE_ADMIN')]
final class UserAdminController
{
    public function __construct(
        private readonly UserLookup $users,
        private readonly UserManager $userManager,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'api_admin_users_list', methods: ['GET'])]
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
    public function show(int $id): JsonResponse
    {
        $user = $this->users->findById($id);

        if (!$user instanceof User || $user->getDeletedAt() !== null) {
            return new JsonResponse(['error' => 'user_not_found'], 404);
        }

        return new JsonResponse($user->toArray());
    }

    #[Route('/{id}', name: 'api_admin_users_update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
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
