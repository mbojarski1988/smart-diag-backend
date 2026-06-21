<?php

declare(strict_types=1);

namespace App\Tests\User\Application;

use App\User\Application\Dto\UserWriteRequest;
use App\User\Application\UserManager;
use App\User\Domain\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserManagerTest extends TestCase
{
    private UserPasswordHasherInterface&MockObject $hasher;
    private InMemoryUserRepository $repo;
    private UserManager $manager;

    protected function setUp(): void
    {
        $this->hasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->hasher->method('hashPassword')->willReturnArgument(1);
        $this->repo = new InMemoryUserRepository();
        $this->manager = new UserManager($this->repo, $this->hasher);
    }

    public function testCreateReturnsUserWithHashedPassword(): void
    {
        $dto = UserWriteRequest::fromRequest($this->jsonRequest([
            'email'     => 'jan@example.com',
            'firstName' => 'Jan',
            'lastName'  => 'Kowalski',
            'role'      => 'ROLE_ADMIN',
            'password'  => 'tajneHaslo123',
        ]));
        self::assertInstanceOf(UserWriteRequest::class, $dto);

        $result = $this->manager->create($dto);

        self::assertInstanceOf(User::class, $result);
        self::assertSame('jan@example.com', $result->getEmail());
        self::assertSame('tajneHaslo123', $result->getPassword());
        self::assertSame('ROLE_ADMIN', $result->getRole());
    }

    public function testCreateReturnsDuplicateErrorWhenEmailExists(): void
    {
        $existing = new User('jan@example.com', 'Jan', 'Kowalski', 'ROLE_ADMIN');
        $this->repo->add($existing);

        $dto = UserWriteRequest::fromRequest($this->jsonRequest([
            'email'     => 'jan@example.com',
            'firstName' => 'Anna',
            'lastName'  => 'Nowak',
            'role'      => 'ROLE_EMPLOYEE',
            'password'  => 'tajneHaslo123',
        ]));
        self::assertInstanceOf(UserWriteRequest::class, $dto);

        $result = $this->manager->create($dto);

        self::assertSame('email_already_exists', $result);
    }

    public function testUpdateChangesUserFields(): void
    {
        $user = new User('jan@example.com', 'Jan', 'Kowalski', 'ROLE_ADMIN');

        $dto = UserWriteRequest::fromPatchRequest($this->jsonRequest([
            'firstName' => 'Anna',
            'lastName'  => 'Nowak',
            'role'      => 'ROLE_EMPLOYEE',
            'active'    => false,
        ]), $user);
        self::assertInstanceOf(UserWriteRequest::class, $dto);

        $this->manager->update($user, $dto);

        self::assertSame('Anna', $user->getFirstName());
        self::assertSame('Nowak', $user->getLastName());
        self::assertSame('ROLE_EMPLOYEE', $user->getRole());
        self::assertFalse($user->isActive());
    }

    public function testResetPasswordUpdatesHash(): void
    {
        $user = new User('jan@example.com', 'Jan', 'Kowalski', 'ROLE_ADMIN');

        $this->manager->resetPassword($user, 'noweHaslo456');

        self::assertSame('noweHaslo456', $user->getPassword());
    }

    public function testFromRequestReturnsMissingEmailError(): void
    {
        $result = UserWriteRequest::fromRequest($this->jsonRequest([
            'firstName' => 'Jan',
            'lastName'  => 'Kowalski',
            'role'      => 'ROLE_ADMIN',
            'password'  => 'tajneHaslo123',
        ]));

        self::assertSame('missing_email', $result);
    }

    public function testFromRequestReturnsInvalidRoleError(): void
    {
        $result = UserWriteRequest::fromRequest($this->jsonRequest([
            'email'     => 'jan@example.com',
            'firstName' => 'Jan',
            'lastName'  => 'Kowalski',
            'role'      => 'ROLE_SUPERUSER',
            'password'  => 'tajneHaslo123',
        ]));

        self::assertSame('invalid_role', $result);
    }

    public function testFromRequestReturnsPasswordTooShortError(): void
    {
        $result = UserWriteRequest::fromRequest($this->jsonRequest([
            'email'     => 'jan@example.com',
            'firstName' => 'Jan',
            'lastName'  => 'Kowalski',
            'role'      => 'ROLE_ADMIN',
            'password'  => 'short',
        ]));

        self::assertSame('password_too_short', $result);
    }

    /** @param array<string, mixed> $data */
    private function jsonRequest(array $data): Request
    {
        return new Request(content: (string) json_encode($data));
    }
}
