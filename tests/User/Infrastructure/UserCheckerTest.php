<?php

declare(strict_types=1);

namespace App\Tests\User\Infrastructure;

use App\User\Domain\User;
use App\User\Infrastructure\UserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

final class UserCheckerTest extends TestCase
{
    private function checker(): UserChecker
    {
        return new UserChecker();
    }

    private function activeUser(): User
    {
        return new User('test@example.com', 'Jan', 'Kowalski', 'ROLE_ADMIN');
    }

    public function testAllowsActiveUser(): void
    {
        $this->checker()->checkPreAuth($this->activeUser());
        $this->expectNotToPerformAssertions();
    }

    public function testBlocksSoftDeletedUser(): void
    {
        $user = $this->activeUser();
        $user->softDelete();

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->checker()->checkPreAuth($user);
    }

    public function testBlocksInactiveUser(): void
    {
        $user = $this->activeUser();
        $user->update('Jan', 'Kowalski', 'ROLE_ADMIN', false);

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->checker()->checkPreAuth($user);
    }
}
