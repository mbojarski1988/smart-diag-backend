<?php

declare(strict_types=1);

namespace App\Shared\Auth\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_CLASS)]
final class RequiresAuth
{
}
