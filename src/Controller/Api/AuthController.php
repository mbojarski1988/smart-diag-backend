<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Shared\Auth\Attribute\RequiresAuth;
use App\User\Domain\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth')]
final class AuthController
{
    public function __construct(private readonly Security $security)
    {
    }

    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    #[RequiresAuth]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->security->getUser();

        return new JsonResponse($user->toArray());
    }
}
