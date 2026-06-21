<?php

declare(strict_types=1);

namespace App\Shared\Ai\Infrastructure\GitHub;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class GitHubAiClient
{
    private const ENDPOINT = 'https://models.github.ai/inference/chat/completions';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $githubToken,
    ) {
    }

    /**
     * @param list<GitHubAiMessage> $messages
     */
    public function complete(
        array $messages,
        string $model = 'meta/Meta-Llama-3.1-405B-Instruct',
        float $temperature = 1.0,
        float $topP = 1.0,
        int $maxTokens = 1000,
    ): GitHubAiResponse {
        $response = $this->httpClient->request('POST', self::ENDPOINT, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->githubToken,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => $model,
                'messages' => array_map(
                    static fn (GitHubAiMessage $m): array => $m->toArray(),
                    $messages,
                ),
                'temperature' => $temperature,
                'top_p' => $topP,
                'max_tokens' => $maxTokens,
            ],
            'timeout' => 30,
        ]);

        return GitHubAiResponse::fromArray($response->toArray());
    }
}
