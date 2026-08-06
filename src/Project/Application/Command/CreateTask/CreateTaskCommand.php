<?php

declare(strict_types=1);

namespace App\Project\Application\Command\CreateTask;

use App\Project\Application\Security\AuthorizedMessage;
use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\Security\RoleRequirement;

/** @implements Command<null> */
final class CreateTaskCommand implements Command, AuthorizedMessage
{
    public function __construct(
        public readonly string $id,
        public readonly string $projectId,
        public readonly string $title,
        public readonly ?string $assigneeId = null,
        public readonly ?string $attachmentId = null,
    ) {
    }

    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::user();
    }
}
