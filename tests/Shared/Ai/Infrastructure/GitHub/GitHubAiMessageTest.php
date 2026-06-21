<?php

declare(strict_types=1);

namespace App\Tests\Shared\Ai\Infrastructure\GitHub;

use App\Shared\Ai\Infrastructure\GitHub\GitHubAiMessage;
use PHPUnit\Framework\TestCase;

final class GitHubAiMessageTest extends TestCase
{
    public function testToArrayReturnsRoleAndContent(): void
    {
        $message = new GitHubAiMessage('user', 'Hello');

        self::assertSame(['role' => 'user', 'content' => 'Hello'], $message->toArray());
    }

    public function testToArrayWithSystemRole(): void
    {
        $message = new GitHubAiMessage('system', 'You are a helpful assistant.');

        self::assertSame('system', $message->toArray()['role']);
        self::assertSame('You are a helpful assistant.', $message->toArray()['content']);
    }
}
