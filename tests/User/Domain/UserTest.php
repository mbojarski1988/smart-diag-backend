<?php

declare(strict_types=1);

namespace App\Tests\User\Domain;

use App\User\Domain\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testNewUserHasCorrectDefaults(): void
    {
        $user = new User('jan@example.com', 'Jan', 'Kowalski', 'ROLE_ADMIN');

        self::assertNull($user->getId());
        self::assertSame('jan@example.com', $user->getEmail());
        self::assertSame('Jan', $user->getFirstName());
        self::assertSame('Kowalski', $user->getLastName());
        self::assertSame('ROLE_ADMIN', $user->getRole());
        self::assertTrue($user->isActive());
        self::assertNull($user->getDeletedAt());
        self::assertSame('', $user->getPassword());
    }

    public function testUpdatePasswordSetsHashedPassword(): void
    {
        $user = new User('jan@example.com', 'Jan', 'Kowalski', 'ROLE_ADMIN');
        $user->updatePassword('hashed_secret');

        self::assertSame('hashed_secret', $user->getPassword());
    }

    public function testUpdateChangesFields(): void
    {
        $user = new User('jan@example.com', 'Jan', 'Kowalski', 'ROLE_ADMIN');
        $user->update('Anna', 'Nowak', 'ROLE_EMPLOYEE', false);

        self::assertSame('Anna', $user->getFirstName());
        self::assertSame('Nowak', $user->getLastName());
        self::assertSame('ROLE_EMPLOYEE', $user->getRole());
        self::assertFalse($user->isActive());
    }

    public function testSoftDeleteSetsDeletedAtAndDeactivates(): void
    {
        $user = new User('jan@example.com', 'Jan', 'Kowalski', 'ROLE_ADMIN');
        $before = new \DateTimeImmutable();
        $user->softDelete();

        self::assertNotNull($user->getDeletedAt());
        self::assertGreaterThanOrEqual($before, $user->getDeletedAt());
        self::assertFalse($user->isActive());
    }

    public function testGetRolesReturnsRoleInArray(): void
    {
        $user = new User('jan@example.com', 'Jan', 'Kowalski', 'ROLE_EMPLOYEE');

        self::assertSame(['ROLE_EMPLOYEE'], $user->getRoles());
    }

    public function testGetUserIdentifierReturnsEmail(): void
    {
        $user = new User('jan@example.com', 'Jan', 'Kowalski', 'ROLE_ADMIN');

        self::assertSame('jan@example.com', $user->getUserIdentifier());
    }

    public function testToArrayContainsAllFields(): void
    {
        $user = new User('jan@example.com', 'Jan', 'Kowalski', 'ROLE_ADMIN');
        $data = $user->toArray();

        self::assertNull($data['id']);
        self::assertSame('jan@example.com', $data['email']);
        self::assertSame('Jan', $data['firstName']);
        self::assertSame('Kowalski', $data['lastName']);
        self::assertSame('ROLE_ADMIN', $data['role']);
        self::assertTrue($data['active']);
        self::assertNull($data['deletedAt']);
        self::assertArrayHasKey('createdAt', $data);
        self::assertArrayHasKey('updatedAt', $data);
    }

    public function testTouchUpdatesUpdatedAt(): void
    {
        $user = new User('jan@example.com', 'Jan', 'Kowalski', 'ROLE_ADMIN');
        $before = $user->getUpdatedAt();
        usleep(1100);
        $user->touch();

        self::assertGreaterThan($before, $user->getUpdatedAt());
    }
}
