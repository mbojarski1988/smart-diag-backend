<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\Api\UserAdminController;
use App\Shared\Auth\Attribute\RequiresRole;
use App\Tests\User\Application\InMemoryUserRepository;
use App\User\Application\UserManager;
use App\User\Domain\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserAdminControllerTest extends TestCase
{
    private InMemoryUserRepository $repo;
    private UserManager $manager;
    private EntityManagerInterface&MockObject $em;
    private UserAdminController $controller;

    protected function setUp(): void
    {
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturnArgument(1);

        $this->repo       = new InMemoryUserRepository();
        $this->manager    = new UserManager($this->repo, $hasher);
        $this->em         = $this->createMock(EntityManagerInterface::class);
        $this->controller = new UserAdminController($this->repo, $this->manager, $this->em);
    }

    public function testListReturnsVisibleUsers(): void
    {
        $this->repo->add(new User('jan@example.com', 'Jan', 'Kowalski', 'ROLE_ADMIN'));

        $response = $this->controller->list();

        self::assertSame(200, $response->getStatusCode());
        /** @var array<string, array<mixed>> $data */
        $data = json_decode((string) $response->getContent(), true);
        self::assertCount(1, $data['items']);
    }

    public function testCreateReturns201WithValidData(): void
    {
        $this->em->expects(self::once())->method('persist');
        $this->em->expects(self::once())->method('flush');

        $request  = $this->jsonRequest([
            'email'     => 'anna@example.com',
            'firstName' => 'Anna',
            'lastName'  => 'Nowak',
            'role'      => 'ROLE_EMPLOYEE',
            'password'  => 'tajneHaslo123',
        ]);
        $response = $this->controller->create($request);

        self::assertSame(201, $response->getStatusCode());
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $response->getContent(), true);
        self::assertSame('anna@example.com', $data['email']);
    }

    public function testCreateReturns400OnMissingField(): void
    {
        $response = $this->controller->create($this->jsonRequest(['email' => 'x@x.com']));

        self::assertSame(422, $response->getStatusCode());
    }

    public function testCreateReturns409OnDuplicateEmail(): void
    {
        $this->repo->add(new User('anna@example.com', 'Anna', 'Nowak', 'ROLE_EMPLOYEE'));

        $request  = $this->jsonRequest([
            'email'     => 'anna@example.com',
            'firstName' => 'Anna',
            'lastName'  => 'Nowak',
            'role'      => 'ROLE_EMPLOYEE',
            'password'  => 'tajneHaslo123',
        ]);
        $response = $this->controller->create($request);

        self::assertSame(422, $response->getStatusCode());
    }

    public function testShowReturns404ForMissingUser(): void
    {
        $response = $this->controller->show(999);

        self::assertSame(404, $response->getStatusCode());
    }

    public function testUpdatePatchesUser(): void
    {
        $user = new User('jan@example.com', 'Jan', 'Kowalski', 'ROLE_ADMIN');
        $this->repo->add($user);
        $this->em->expects(self::once())->method('flush');

        $response = $this->controller->update(
            $user->getId() ?? 0,
            $this->jsonRequest(['firstName' => 'Janusz']),
        );

        self::assertSame(200, $response->getStatusCode());
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $response->getContent(), true);
        self::assertSame('Janusz', $data['firstName']);
    }

    public function testResetPasswordReturns400OnShortPassword(): void
    {
        $user = new User('jan@example.com', 'Jan', 'Kowalski', 'ROLE_ADMIN');
        $this->repo->add($user);

        $response = $this->controller->resetPassword(
            $user->getId() ?? 0,
            $this->jsonRequest(['password' => 'short']),
        );

        self::assertSame(422, $response->getStatusCode());
    }

    public function testDeleteSoftDeletesUser(): void
    {
        $user = new User('jan@example.com', 'Jan', 'Kowalski', 'ROLE_ADMIN');
        $this->repo->add($user);
        $this->em->expects(self::once())->method('flush');

        $response = $this->controller->delete($user->getId() ?? 0);

        self::assertSame(200, $response->getStatusCode());
        self::assertNotNull($user->getDeletedAt());
    }

    public function testControllerRequiresAdminRole(): void
    {
        $ref   = new \ReflectionClass(UserAdminController::class);
        $attrs = $ref->getAttributes(RequiresRole::class);
        self::assertNotEmpty($attrs, 'UserAdminController must declare #[RequiresRole]');
        /** @var RequiresRole $roleAttr */
        $roleAttr = $attrs[0]->newInstance();
        self::assertSame('ROLE_ADMIN', $roleAttr->role);
    }

    /** @param array<string, mixed> $data */
    private function jsonRequest(array $data): Request
    {
        return new Request(content: (string) json_encode($data));
    }
}
