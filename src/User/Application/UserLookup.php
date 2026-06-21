<?php

declare(strict_types=1);

namespace App\User\Application;

use App\User\Domain\User;

interface UserLookup
{
    public function findByEmail(string $email): ?User;

    public function findById(int $id): ?User;

    /** @return list<User> */
    public function findVisible(): array;
}
