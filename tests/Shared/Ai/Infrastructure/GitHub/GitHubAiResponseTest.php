<?php

declare(strict_types=1);

namespace App\Tests\Shared\Ai\Infrastructure\GitHub;

use App\Shared\Ai\Infrastructure\GitHub\GitHubAiResponse;
use PHPUnit\Framework\TestCase;

final class GitHubAiResponseTest extends TestCase
{
    public function testFromArrayExtractsContentModelAndFinishReason(): void
    {
        $data = [
            'model' => 'meta/Meta-Llama-3.1-405B-Instruct',
            'choices' => [
                [
                    'message' => ['role' => 'assistant', 'content' => 'Hello there!'],
                    'finish_reason' => 'stop',
                ],
            ],
        ];

        $response = GitHubAiResponse::fromArray($data);

        self::assertSame('Hello there!', $response->content);
        self::assertSame('meta/Meta-Llama-3.1-405B-Instruct', $response->model);
        self::assertSame('stop', $response->finishReason);
        self::assertSame($data, $response->raw);
    }

    public function testFromArrayHandlesEmptyChoices(): void
    {
        $data = ['model' => 'some-model', 'choices' => []];

        $response = GitHubAiResponse::fromArray($data);

        self::assertSame('', $response->content);
        self::assertSame('some-model', $response->model);
        self::assertSame('', $response->finishReason);
    }

    public function testFromArrayHandlesMissingFields(): void
    {
        $response = GitHubAiResponse::fromArray([]);

        self::assertSame('', $response->content);
        self::assertSame('', $response->model);
        self::assertSame('', $response->finishReason);
    }

    public function testToArrayReturnsRawData(): void
    {
        $data = ['model' => 'x', 'choices' => []];
        $response = GitHubAiResponse::fromArray($data);

        self::assertSame($data, $response->toArray());
    }
}
