<?php

declare(strict_types=1);

namespace App\Project\Application\Command\ReplaceTask;

use App\Project\Application\Security\AuthorizedMessage;
use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\Security\RoleRequirement;

/** @implements Command<null> */
final class ReplaceTaskCommand implements Command, AuthorizedMessage
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly ?string $assigneeId = null,
    ) {
    }

    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::user();
    }
}
