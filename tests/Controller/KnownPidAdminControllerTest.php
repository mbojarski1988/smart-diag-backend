<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\Api\KnownPidAdminController;
use App\Shared\Auth\Attribute\RequiresRole;
use PHPUnit\Framework\TestCase;

final class KnownPidAdminControllerTest extends TestCase
{
    public function testControllerRequiresAdminRole(): void
    {
        $ref = new \ReflectionClass(KnownPidAdminController::class);
        $attrs = $ref->getAttributes(RequiresRole::class);

        self::assertNotEmpty($attrs, 'KnownPidAdminController must declare #[RequiresRole]');

        /** @var RequiresRole $roleAttr */
        $roleAttr = $attrs[0]->newInstance();
        self::assertSame('ROLE_ADMIN', $roleAttr->role);
    }
}
