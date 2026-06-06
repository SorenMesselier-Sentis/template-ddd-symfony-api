<?php

declare(strict_types=1);

namespace App\Shared\Domain\Security;

interface AuthorizedMessageContract
{
    public function roleRequirement(): RoleRequirement;
}
