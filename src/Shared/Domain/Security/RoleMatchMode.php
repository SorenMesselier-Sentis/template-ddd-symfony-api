<?php

declare(strict_types=1);

namespace App\Shared\Domain\Security;

enum RoleMatchMode
{
    case Any;
    case All;
}
