<?php

declare(strict_types=1);

namespace App\User\Application\Security;

enum RoleMatchMode
{
    case Any;
    case All;
}
