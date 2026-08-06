<?php

declare(strict_types=1);

namespace App\Project\Application\Command\DeleteTask;

use App\Project\Application\Security\AuthorizedMessage;
use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\Security\RoleRequirement;

/** @implements Command<null> */
final class DeleteTaskCommand implements Command, AuthorizedMessage
{
    public function __construct(
        public readonly string $id,
    ) {
    }

    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::user();
    }
}
