<?php

declare(strict_types=1);

namespace App\Shared\Auth\EventListener;

use App\User\Domain\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'lexik_jwt_authentication.on_authentication_success')]
final readonly class JwtAuthSuccessListener
{
    public function __invoke(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $data         = $event->getData();
        $data['user'] = $user->toArray();
        $event->setData($data);
    }
}
