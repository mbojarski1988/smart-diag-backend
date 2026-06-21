<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\Api\AuthController;
use App\User\Domain\User;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;

final class AuthControllerTest extends TestCase
{
    public function testMeReturnsCurrentUserData(): void
    {
        $user = new User('jan@example.com', 'Jan', 'Kowalski', 'ROLE_ADMIN');

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        $controller = new AuthController($security);
        $response   = $controller->me();

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());

        /** @var array<string, mixed> $data */
        $data = json_decode((string) $response->getContent(), true);
        self::assertSame('jan@example.com', $data['email']);
        self::assertSame('Jan', $data['firstName']);
        self::assertSame('Kowalski', $data['lastName']);
        self::assertSame('ROLE_ADMIN', $data['role']);
        self::assertTrue($data['active']);
    }
}
