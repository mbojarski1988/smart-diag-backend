<?php

declare(strict_types=1);

namespace App\Shared\Auth\EventListener;

use App\License\Application\LicenseValidator;
use App\Shared\Auth\Attribute\RequiresAdmin;
use App\Shared\Auth\Attribute\RequiresLicense;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class AuthorizationListener implements EventSubscriberInterface
{
    public function __construct(
        private LicenseValidator $licenseValidator,
        #[Autowire('%env(ADMIN_API_KEY)%')]
        private string $adminApiKey,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER => ['onKernelController', 10]];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if ($event->getAttributes(RequiresAdmin::class)) {
            $provided = (string) $event->getRequest()->headers->get('X-Admin-Key', '');

            if ($provided === '' || !hash_equals($this->adminApiKey, $provided)) {
                $event->setController(static fn () => new JsonResponse(['error' => 'invalid_admin_key'], 401));

                return;
            }
        }

        if ($event->getAttributes(RequiresLicense::class)) {
            $result = $this->licenseValidator->validate(
                $event->getRequest()->headers->get('X-License-Key'),
            );

            if (!$result->valid) {
                $event->setController(
                    static fn () => new JsonResponse(['error' => $result->errorCode], $result->statusCode),
                );
            }
        }
    }
}
