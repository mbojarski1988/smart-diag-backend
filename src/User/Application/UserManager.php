<?php

declare(strict_types=1);

namespace App\User\Application;

use App\User\Application\Dto\UserWriteRequest;
use App\User\Domain\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class UserManager
{
    public function __construct(
        private UserLookup $users,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * @return User|non-empty-string
     */
    public function create(UserWriteRequest $dto): User|string
    {
        if ($this->users->findByEmail($dto->email) !== null) {
            return 'email_already_exists';
        }

        $user = new User(
            email: $dto->email,
            firstName: $dto->firstName,
            lastName: $dto->lastName,
            role: $dto->role,
        );

        $user->updatePassword($this->passwordHasher->hashPassword($user, $dto->password));

        return $user;
    }

    public function update(User $user, UserWriteRequest $dto): void
    {
        $user->update(
            firstName: $dto->firstName,
            lastName: $dto->lastName,
            role: $dto->role,
            active: $dto->active ?? $user->isActive(),
        );
    }

    public function resetPassword(User $user, string $newPassword): void
    {
        $user->updatePassword($this->passwordHasher->hashPassword($user, $newPassword));
    }
}
