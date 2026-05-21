<?php

declare(strict_types=1);

namespace App\User\Application\Security;

interface AuthorizedMessage
{
    public function roleRequirement(): RoleRequirement;
}
