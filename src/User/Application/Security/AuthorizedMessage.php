<?php

declare(strict_types=1);

namespace App\User\Application\Security;

use App\Shared\Domain\Security\AuthorizedMessageContract;

interface AuthorizedMessage extends AuthorizedMessageContract
{
    public function roleRequirement(): RoleRequirement;
}
