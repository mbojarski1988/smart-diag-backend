<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Shared\Ai\Application\Dto\AiPromptWriteRequest;
use App\Shared\Ai\Domain\AiPrompt;
use App\Shared\Ai\Infrastructure\AiPromptRepository;
use App\Shared\Auth\Attribute\RequiresRole;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/ai-prompts')]
#[RequiresRole('ROLE_ADMIN')]
#[OA\Tag(name: 'AI prompt administration')]
final class AiPromptAdminController
{
    public function __construct(
        private readonly AiPromptRepository $prompts,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'api_admin_ai_prompts_list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/admin/ai-prompts',
        operationId: 'adminAiPromptsList',
        summary: 'List AI prompts',
        security: [['BearerAuth' => []]],
        tags: ['AI prompt administration'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of AI prompts.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'items',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/AiPrompt'),
                        ),
                    ],
                    type: 'object',
                ),
            ),
        ],
    )]
    public function list(): JsonResponse
    {
        return new JsonResponse([
            'items' => array_map(
                static fn (AiPrompt $prompt): array => $prompt->toArray(),
                $this->prompts->findAllOrdered(),
            ),
        ]);
    }

    #[Route('', name: 'api_admin_ai_prompts_create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/admin/ai-prompts',
        operationId: 'adminAiPromptsCreate',
        summary: 'Create an AI prompt',
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AiPromptWriteRequest'),
        ),
        tags: ['AI prompt administration'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'AI prompt created.',
                content: new OA\JsonContent(ref: '#/components/schemas/AiPrompt'),
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function create(Request $request): JsonResponse
    {
        $dto = AiPromptWriteRequest::fromRequest($request);

        if (is_string($dto)) {
            return new JsonResponse(['error' => $dto], 422);
        }

        $prompt = new AiPrompt($dto->name, $dto->prompt);

        $this->entityManager->persist($prompt);
        $this->entityManager->flush();

        return new JsonResponse($prompt->toArray(), 201);
    }

    #[Route('/{id}', name: 'api_admin_ai_prompts_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[OA\Get(
        path: '/api/admin/ai-prompts/{id}',
        operationId: 'adminAiPromptsShow',
        summary: 'Get an AI prompt',
        security: [['BearerAuth' => []]],
        tags: ['AI prompt administration'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'AI prompt data.',
                content: new OA\JsonContent(ref: '#/components/schemas/AiPrompt'),
            ),
            new OA\Response(
                response: 404,
                description: 'AI prompt not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function show(int $id): JsonResponse
    {
        $prompt = $this->findPrompt($id);

        if (!$prompt instanceof AiPrompt) {
            return new JsonResponse(['error' => 'ai_prompt_not_found'], 404);
        }

        return new JsonResponse($prompt->toArray());
    }

    #[Route('/{id}', name: 'api_admin_ai_prompts_update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    #[OA\Patch(
        path: '/api/admin/ai-prompts/{id}',
        operationId: 'adminAiPromptsUpdate',
        summary: 'Update an AI prompt',
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AiPromptWriteRequest'),
        ),
        tags: ['AI prompt administration'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Updated AI prompt data.',
                content: new OA\JsonContent(ref: '#/components/schemas/AiPrompt'),
            ),
            new OA\Response(
                response: 404,
                description: 'AI prompt not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function update(int $id, Request $request): JsonResponse
    {
        $prompt = $this->findPrompt($id);

        if (!$prompt instanceof AiPrompt) {
            return new JsonResponse(['error' => 'ai_prompt_not_found'], 404);
        }

        $dto = AiPromptWriteRequest::fromPatchRequest($request, $prompt);

        if (is_string($dto)) {
            return new JsonResponse(['error' => $dto], 422);
        }

        $prompt->update($dto->name, $dto->prompt);
        $this->entityManager->flush();

        return new JsonResponse($prompt->toArray());
    }

    #[Route('/{id}', name: 'api_admin_ai_prompts_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/admin/ai-prompts/{id}',
        operationId: 'adminAiPromptsDelete',
        summary: 'Delete an AI prompt',
        security: [['BearerAuth' => []]],
        tags: ['AI prompt administration'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Deleted AI prompt data.',
                content: new OA\JsonContent(ref: '#/components/schemas/AiPrompt'),
            ),
            new OA\Response(
                response: 404,
                description: 'AI prompt not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
            ),
        ],
    )]
    public function delete(int $id): JsonResponse
    {
        $prompt = $this->findPrompt($id);

        if (!$prompt instanceof AiPrompt) {
            return new JsonResponse(['error' => 'ai_prompt_not_found'], 404);
        }

        $data = $prompt->toArray();
        $this->entityManager->remove($prompt);
        $this->entityManager->flush();

        return new JsonResponse($data);
    }

    private function findPrompt(int $id): ?AiPrompt
    {
        $prompt = $this->prompts->find($id);

        return $prompt instanceof AiPrompt ? $prompt : null;
    }
}
