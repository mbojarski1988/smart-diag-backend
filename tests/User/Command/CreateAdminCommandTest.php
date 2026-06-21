<?php

declare(strict_types=1);

namespace App\Tests\User\Command;

use App\Tests\User\Application\InMemoryUserRepository;
use App\User\Application\UserManager;
use App\User\Command\CreateAdminCommand;
use App\User\Domain\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CreateAdminCommandTest extends TestCase
{
    private InMemoryUserRepository $repo;
    private EntityManagerInterface&MockObject $em;
    private CommandTester $tester;

    protected function setUp(): void
    {
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturnArgument(1);

        $this->repo   = new InMemoryUserRepository();
        $this->em     = $this->createMock(EntityManagerInterface::class);
        $manager      = new UserManager($this->repo, $hasher);
        $command      = new CreateAdminCommand($manager, $this->em);
        $this->tester = new CommandTester($command);
    }

    public function testItCreatesAdminUser(): void
    {
        $this->em->expects(self::once())->method('persist')->with(self::isInstanceOf(User::class));
        $this->em->expects(self::once())->method('flush');

        $exitCode = $this->tester->execute([
            '--email'     => 'admin@example.com',
            '--firstName' => 'Jan',
            '--lastName'  => 'Kowalski',
            '--password'  => 'tajneHaslo123',
        ]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('admin@example.com', $this->tester->getDisplay());
    }

    public function testItCreatesUserWithRoleAdmin(): void
    {
        $capturedUser = null;
        $this->em->method('persist')->willReturnCallback(
            static function (mixed $user) use (&$capturedUser): void {
                $capturedUser = $user;
            },
        );

        $this->tester->execute([
            '--email'     => 'admin@example.com',
            '--firstName' => 'Jan',
            '--lastName'  => 'Kowalski',
            '--password'  => 'tajneHaslo123',
        ]);

        self::assertInstanceOf(User::class, $capturedUser);
        self::assertSame('ROLE_ADMIN', $capturedUser->getRole());
        self::assertSame('admin@example.com', $capturedUser->getEmail());
    }

    public function testItFailsWhenEmailMissing(): void
    {
        $exitCode = $this->tester->execute([
            '--firstName' => 'Jan',
            '--lastName'  => 'Kowalski',
            '--password'  => 'tajneHaslo123',
        ]);

        self::assertSame(1, $exitCode);
    }

    public function testItFailsWhenEmailAlreadyExists(): void
    {
        $this->repo->add(new User('admin@example.com', 'Jan', 'Kowalski', 'ROLE_ADMIN'));

        $exitCode = $this->tester->execute([
            '--email'     => 'admin@example.com',
            '--firstName' => 'Jan',
            '--lastName'  => 'Kowalski',
            '--password'  => 'tajneHaslo123',
        ]);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('email_already_exists', $this->tester->getDisplay());
    }
}
