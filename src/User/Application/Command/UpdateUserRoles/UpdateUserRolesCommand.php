<?php

declare(strict_types=1);

namespace App\User\Application\Command\UpdateUserRoles;

use App\Shared\Domain\Bus\Command\Command;
use App\User\Application\Security\AuthorizedMessage;
use App\Shared\Domain\Security\RoleRequirement;

final class UpdateUserRolesCommand implements Command, AuthorizedMessage
{
    /**
     * @param array<int,mixed> $roles
     */
    public function __construct(
        public readonly string $id,
        public readonly array $roles,
    ) {
    }

    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::admin();
    }
}
