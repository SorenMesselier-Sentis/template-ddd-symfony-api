<?php

declare(strict_types=1);

namespace App\Project\Application\Command\CreateProject;

use App\Project\Application\Security\AuthorizedMessage;
use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\Security\RoleRequirement;

/** @implements Command<null> */
final class CreateProjectCommand implements Command, AuthorizedMessage
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
    ) {
    }

    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::user();
    }
}
