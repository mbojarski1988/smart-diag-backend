<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Pid\Application\Dto\KnownPidWriteRequest;
use App\Pid\Domain\KnownPid;
use App\Pid\Infrastructure\KnownPidRepository;
use App\Shared\Auth\Attribute\RequiresRole;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/known-pids')]
#[RequiresRole('ROLE_ADMIN')]
#[OA\Tag(name: 'Known PID administration')]
final class KnownPidAdminController
{
    public function __construct(
        private readonly KnownPidRepository $knownPids,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'api_admin_known_pids_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $model = $request->query->get('model');

        return new JsonResponse([
            'items' => array_map(
                static fn (KnownPid $knownPid): array => $knownPid->toArray(),
                $this->knownPids->findForAdmin(is_string($model) ? $model : null),
            ),
        ]);
    }

    #[Route('', name: 'api_admin_known_pids_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $dto = KnownPidWriteRequest::fromRequest($request);

        if (is_string($dto)) {
            return new JsonResponse(['error' => $dto], 422);
        }

        $knownPid = new KnownPid(
            model: $dto->model,
            pid: $dto->pid,
            name: $dto->name,
            unit: $dto->unit,
            description: $dto->description,
            active: $dto->active,
        );

        $this->entityManager->persist($knownPid);

        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            return new JsonResponse(['error' => 'known_pid_already_exists'], 409);
        }

        return new JsonResponse($knownPid->toArray(), 201);
    }

    #[Route('/{id}', name: 'api_admin_known_pids_update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $knownPid = $this->findKnownPid($id);

        if (!$knownPid instanceof KnownPid) {
            return new JsonResponse(['error' => 'known_pid_not_found'], 404);
        }

        $dto = KnownPidWriteRequest::fromPatchRequest($request, $knownPid);

        if (is_string($dto)) {
            return new JsonResponse(['error' => $dto], 422);
        }

        $duplicate = $this->knownPids->findOneByModelAndPid($dto->model, $dto->pid);

        if ($duplicate instanceof KnownPid && $duplicate->getId() !== $knownPid->getId()) {
            return new JsonResponse(['error' => 'known_pid_already_exists'], 409);
        }

        $knownPid->update(
            model: $dto->model,
            pid: $dto->pid,
            name: $dto->name,
            unit: $dto->unit,
            description: $dto->description,
            active: $dto->active,
        );
        $this->entityManager->flush();

        return new JsonResponse($knownPid->toArray());
    }

    #[Route('/{id}', name: 'api_admin_known_pids_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $knownPid = $this->findKnownPid($id);

        if (!$knownPid instanceof KnownPid) {
            return new JsonResponse(['error' => 'known_pid_not_found'], 404);
        }

        $data = $knownPid->toArray();
        $this->entityManager->remove($knownPid);
        $this->entityManager->flush();

        return new JsonResponse($data);
    }

    private function findKnownPid(int $id): ?KnownPid
    {
        $knownPid = $this->knownPids->find($id);

        return $knownPid instanceof KnownPid ? $knownPid : null;
    }
}
