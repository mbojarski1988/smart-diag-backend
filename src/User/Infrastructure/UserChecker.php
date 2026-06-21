<?php

declare(strict_types=1);

namespace App\User\Infrastructure;

use App\User\Domain\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->getDeletedAt() !== null || !$user->isActive()) {
            throw new CustomUserMessageAccountStatusException('account_inactive');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
