<?php

declare(strict_types=1);

namespace App\User\Application\Command\DeleteUser;

use App\Shared\Domain\Bus\Command\Command;
use App\User\Application\Security\AuthorizedMessage;
use App\User\Application\Security\RoleRequirement;

final class DeleteUserCommand implements Command, AuthorizedMessage
{
    public function __construct(
        public readonly string $id,
    ) {
    }

    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::admin();
    }
}
