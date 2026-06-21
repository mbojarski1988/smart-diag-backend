<?php

declare(strict_types=1);

namespace App\Shared\Ai\Infrastructure\GitHub;

final readonly class GitHubAiMessage
{
    public function __construct(
        public string $role,
        public string $content,
    ) {
    }

    /** @return array{role: string, content: string} */
    public function toArray(): array
    {
        return ['role' => $this->role, 'content' => $this->content];
    }
}
