<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Shared\Auth\Attribute\RequiresAuth;
use App\User\Domain\User;
use LogicException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/auth')]
final class AuthController
{
    public function __construct(private readonly Security $security)
    {
    }

    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(): never
    {
        throw new LogicException('Handled by the security firewall — this method is never called.');
    }

    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    #[RequiresAuth]
    #[OA\Get(
        path: '/api/auth/me',
        operationId: 'authMe',
        summary: 'Return the currently authenticated user',
        security: [['BearerAuth' => []]],
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Authenticated user data.',
                content: new OA\JsonContent(
                    type: 'object',
                    example: ['id' => 1, 'email' => 'admin@example.com', 'roles' => ['ROLE_ADMIN']],
                ),
            ),
            new OA\Response(
                response: 401,
                description: 'Missing or invalid JWT token.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->security->getUser();

        return new JsonResponse($user->toArray());
    }
}
