<?php

declare(strict_types=1);

namespace App\Tests\Shared\Ai\Infrastructure\GitHub;

use App\Shared\Ai\Infrastructure\GitHub\GitHubAiClient;
use App\Shared\Ai\Infrastructure\GitHub\GitHubAiMessage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class GitHubAiClientTest extends TestCase
{
    public function testCompleteReturnsParsedResponse(): void
    {
        $payload = [
            'model' => 'meta/Meta-Llama-3.1-405B-Instruct',
            'choices' => [
                [
                    'message' => ['role' => 'assistant', 'content' => 'Answer'],
                    'finish_reason' => 'stop',
                ],
            ],
        ];

        $httpClient = new MockHttpClient(
            new MockResponse(json_encode($payload, JSON_THROW_ON_ERROR)),
        );

        $client = new GitHubAiClient($httpClient, 'test-token');
        $response = $client->complete([new GitHubAiMessage('user', 'Question')]);

        self::assertSame('Answer', $response->content);
        self::assertSame('meta/Meta-Llama-3.1-405B-Instruct', $response->model);
        self::assertSame('stop', $response->finishReason);
    }

    public function testCompleteSendsAuthorizationHeader(): void
    {
        $capturedOptions = [];

        $httpClient = new MockHttpClient(
            function (string $method, string $url, array $options) use (&$capturedOptions): MockResponse {
                $capturedOptions = $options;

                return new MockResponse(json_encode([
                    'model' => 'x',
                    'choices' => [['message' => ['content' => ''], 'finish_reason' => 'stop']],
                ], JSON_THROW_ON_ERROR));
            },
        );

        $client = new GitHubAiClient($httpClient, 'my-secret-token');
        $client->complete([new GitHubAiMessage('user', 'Hi')]);

        $headers = $capturedOptions['headers'] ?? [];
        self::assertContains('Authorization: Bearer my-secret-token', $headers);
    }

    public function testCompletePassesMessagesToPayload(): void
    {
        $capturedBody = [];

        $httpClient = new MockHttpClient(
            function (string $method, string $url, array $options) use (&$capturedBody): MockResponse {
                /** @var string $json */
                $json = $options['body'] ?? '{}';
                /** @var array<string, mixed> $capturedBody */
                $capturedBody = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

                return new MockResponse(json_encode([
                    'model' => 'x',
                    'choices' => [['message' => ['content' => ''], 'finish_reason' => 'stop']],
                ], JSON_THROW_ON_ERROR));
            },
        );

        $client = new GitHubAiClient($httpClient, 'token');
        $client->complete(
            [new GitHubAiMessage('system', 'Be helpful'), new GitHubAiMessage('user', 'Hi')],
            model: 'gpt-4o',
        );

        self::assertSame('gpt-4o', $capturedBody['model']);

        /** @var list<array<string, string>> $messages */
        $messages = $capturedBody['messages'];
        self::assertCount(2, $messages);
        self::assertSame('system', $messages[0]['role']);
        self::assertSame('user', $messages[1]['role']);
    }
}
