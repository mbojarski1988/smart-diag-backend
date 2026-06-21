<?php

declare(strict_types=1);

namespace App\Tests\User\Application;

use App\User\Application\UserLookup;
use App\User\Domain\User;

final class InMemoryUserRepository implements UserLookup
{
    /** @var list<User> */
    private array $users = [];

    private int $nextId = 1;

    public function add(User $user): void
    {
        // Simulate Doctrine's auto-increment ID assignment
        $prop = new \ReflectionProperty(User::class, 'id');
        $prop->setValue($user, $this->nextId++);
        $this->users[] = $user;
    }

    public function findByEmail(string $email): ?User
    {
        foreach ($this->users as $user) {
            if ($user->getEmail() === $email) {
                return $user;
            }
        }

        return null;
    }

    public function findById(int $id): ?User
    {
        foreach ($this->users as $user) {
            if ($user->getId() === $id) {
                return $user;
            }
        }

        return null;
    }

    /**
     * @return list<User>
     */
    public function findVisible(): array
    {
        return array_values(array_filter(
            $this->users,
            static fn (User $u): bool => $u->getDeletedAt() === null,
        ));
    }
}
