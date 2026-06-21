<?php

declare(strict_types=1);

namespace App\Shared\Ai\Infrastructure\GitHub;

final readonly class GitHubAiResponse
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        public string $content,
        public string $model,
        public string $finishReason,
        public array $raw,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        /** @var array<int, array<string, mixed>> $choices */
        $choices = is_array($data['choices'] ?? null) ? $data['choices'] : [];

        $first = $choices[0] ?? [];

        /** @var array<string, mixed> $message */
        $message = is_array($first['message'] ?? null) ? $first['message'] : [];

        return new self(
            content: is_string($message['content'] ?? null) ? $message['content'] : '',
            model: is_string($data['model'] ?? null) ? $data['model'] : '',
            finishReason: is_string($first['finish_reason'] ?? null) ? $first['finish_reason'] : '',
            raw: $data,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->raw;
    }
}
