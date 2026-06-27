<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Pid\Domain\KnownPid;
use App\Pid\Infrastructure\KnownPidRepository;
use App\Shared\Auth\Attribute\RequiresLicense;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/known-pids')]
final class KnownPidController
{
    #[Route('/{model}', name: 'api_known_pids_by_model', methods: ['GET'])]
    #[RequiresLicense]
    public function byModel(string $model, KnownPidRepository $knownPids): JsonResponse
    {
        return new JsonResponse([
            'items' => array_map(
                static fn (KnownPid $knownPid): array => $knownPid->toArray(),
                $knownPids->findActiveByModel($model),
            ),
        ]);
    }
}
