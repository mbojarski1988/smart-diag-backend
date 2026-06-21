<?php

declare(strict_types=1);

namespace App\Shared\Auth\EventListener;

use App\User\Domain\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'lexik_jwt_authentication.on_jwt_created')]
final readonly class JwtCreatedListener
{
    public function __invoke(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $payload = $event->getData();
        $payload['id']        = $user->getId();
        $payload['firstName'] = $user->getFirstName();
        $payload['lastName']  = $user->getLastName();
        $payload['role']      = $user->getRole();
        $event->setData($payload);
    }
}
