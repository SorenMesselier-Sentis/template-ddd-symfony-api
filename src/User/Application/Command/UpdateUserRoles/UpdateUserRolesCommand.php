<?php

declare(strict_types=1);

namespace App\User\Application\Command\UpdateUserRoles;

use App\Shared\Domain\Bus\Command\Command;

final class UpdateUserRolesCommand implements Command
{
    /**
     * @param array<int,mixed> $roles
     */
    public function __construct(
        public readonly string $id,
        public readonly array $roles,
    ) {}
}
