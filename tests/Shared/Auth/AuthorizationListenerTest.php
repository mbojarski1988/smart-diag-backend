<?php

declare(strict_types=1);

namespace App\Tests\Shared\Auth;

use App\License\Application\LicenseValidator;
use App\License\Domain\License;
use App\Shared\Auth\Attribute\RequiresAdmin;
use App\Shared\Auth\Attribute\RequiresAuth;
use App\Shared\Auth\Attribute\RequiresLicense;
use App\Shared\Auth\Attribute\RequiresRole;
use App\Shared\Auth\EventListener\AuthorizationListener;
use App\Tests\License\Application\InMemoryLicenseLookup;
use App\User\Domain\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class AuthorizationListenerTest extends TestCase
{
    private const ADMIN_KEY = 'test-admin-key';
    private const LICENSE_KEY = 'lic_valid_key_for_test';

    public function testItAllowsRequestWithValidLicenseKey(): void
    {
        $license = $this->activeLicense();
        $listener = $this->listener(lookup: new InMemoryLicenseLookup($license));

        $event = $this->makeEvent(
            $this->controllerWith(RequiresLicense::class),
            ['X-License-Key' => self::LICENSE_KEY],
        );

        $originalController = $event->getController();
        $listener->onKernelController($event);

        self::assertSame($originalController, $event->getController());
    }

    public function testItBlocksRequestWithMissingLicenseKey(): void
    {
        $listener = $this->listener(lookup: new InMemoryLicenseLookup());

        $event = $this->makeEvent($this->controllerWith(RequiresLicense::class));
        $listener->onKernelController($event);

        $response = ($event->getController())();
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(401, $response->getStatusCode());
    }

    public function testItBlocksRequestWithInvalidLicenseKey(): void
    {
        $listener = $this->listener(lookup: new InMemoryLicenseLookup());

        $event = $this->makeEvent(
            $this->controllerWith(RequiresLicense::class),
            ['X-License-Key' => 'lic_unknown'],
        );

        $listener->onKernelController($event);

        $response = ($event->getController())();
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(403, $response->getStatusCode());
    }

    public function testItAllowsAdminRequestWithCorrectKey(): void
    {
        $listener = $this->listener();

        $event = $this->makeEvent(
            $this->controllerWith(RequiresAdmin::class),
            ['X-Admin-Key' => self::ADMIN_KEY],
        );

        $originalController = $event->getController();
        $listener->onKernelController($event);

        self::assertSame($originalController, $event->getController());
    }

    public function testItBlocksAdminRequestWithWrongKey(): void
    {
        $listener = $this->listener();

        $event = $this->makeEvent(
            $this->controllerWith(RequiresAdmin::class),
            ['X-Admin-Key' => 'wrong-key'],
        );

        $listener->onKernelController($event);

        $response = ($event->getController())();
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(401, $response->getStatusCode());
    }

    public function testItIgnoresRoutesWithNoAttributes(): void
    {
        $listener = $this->listener();
        $controller = static fn () => new JsonResponse(['status' => 'ok']);
        $event = $this->makeEvent($controller);

        $originalController = $event->getController();
        $listener->onKernelController($event);

        self::assertSame($originalController, $event->getController());
    }

    public function testItAllowsRequestWithRequiresAuthWhenUserLoggedIn(): void
    {
        $user = new User('jan@example.com', 'Jan', 'Kowalski', 'ROLE_EMPLOYEE');
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        $event = $this->makeEvent($this->controllerWith(RequiresAuth::class));
        $originalController = $event->getController();

        $this->listener(security: $security)->onKernelController($event);

        self::assertSame($originalController, $event->getController());
    }

    public function testItBlocksRequiresAuthWhenNoUser(): void
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(null);

        $event = $this->makeEvent($this->controllerWith(RequiresAuth::class));

        $this->listener(security: $security)->onKernelController($event);

        $response = ($event->getController())();
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(401, $response->getStatusCode());
    }

    public function testItAllowsRequiresRoleWhenUserHasRole(): void
    {
        $user = new User('jan@example.com', 'Jan', 'Kowalski', 'ROLE_ADMIN');
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        $event = $this->makeEvent($this->controllerWith(RequiresRole::class));
        $originalController = $event->getController();

        $this->listener(security: $security)->onKernelController($event);

        self::assertSame($originalController, $event->getController());
    }

    public function testItBlocksRequiresRoleWhenUserLacksRole(): void
    {
        $user = new User('jan@example.com', 'Jan', 'Kowalski', 'ROLE_EMPLOYEE');
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        $event = $this->makeEvent($this->controllerWith(RequiresRole::class));

        $this->listener(security: $security)->onKernelController($event);

        $response = ($event->getController())();
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(403, $response->getStatusCode());
    }

    private function listener(
        ?InMemoryLicenseLookup $lookup = null,
        ?Security $security = null,
    ): AuthorizationListener {
        return new AuthorizationListener(
            new LicenseValidator($lookup ?? new InMemoryLicenseLookup()),
            self::ADMIN_KEY,
            $security ?? $this->createMock(Security::class),
        );
    }

    private function activeLicense(): License
    {
        return new License(
            licenseKey: self::LICENSE_KEY,
            clientName: 'Test Client',
            clientEmail: 'test@example.com',
            note: null,
            validUntil: new \DateTimeImmutable('+1 year'),
        );
    }

    /**
     * @param array<string, string> $headers
     */
    private function makeEvent(callable $controller, array $headers = []): ControllerEvent
    {
        /** @var HttpKernelInterface&MockObject $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/');

        foreach ($headers as $name => $value) {
            $request->headers->set($name, $value);
        }

        return new ControllerEvent($kernel, $controller, $request, HttpKernelInterface::MAIN_REQUEST);
    }

    /**
     * @param class-string $attributeClass
     */
    private function controllerWith(string $attributeClass): callable
    {
        return match ($attributeClass) {
            RequiresLicense::class => new class {
                #[RequiresLicense]
                public function __invoke(): JsonResponse
                {
                    return new JsonResponse(['ok' => true]);
                }
            },
            RequiresAdmin::class => new class {
                #[RequiresAdmin]
                public function __invoke(): JsonResponse
                {
                    return new JsonResponse(['ok' => true]);
                }
            },
            RequiresAuth::class => new class {
                #[RequiresAuth]
                public function __invoke(): JsonResponse
                {
                    return new JsonResponse(['ok' => true]);
                }
            },
            RequiresRole::class => new class {
                #[RequiresRole('ROLE_ADMIN')]
                public function __invoke(): JsonResponse
                {
                    return new JsonResponse(['ok' => true]);
                }
            },
            default => static fn () => new JsonResponse(),
        };
    }
}
