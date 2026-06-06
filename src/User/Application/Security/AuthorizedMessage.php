<?php

declare(strict_types=1);

namespace App\User\Application\Security;

use App\Shared\Domain\Security\AuthorizedMessageContract;
use App\Shared\Domain\Security\RoleRequirement;

interface AuthorizedMessage extends AuthorizedMessageContract
{
    public function roleRequirement(): RoleRequirement;
}
