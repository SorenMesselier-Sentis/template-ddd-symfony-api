<?php

declare(strict_types=1);

namespace App\Project\Application\Command\DeleteProject;

use App\Project\Application\Security\AuthorizedMessage;
use App\Shared\Domain\Audit\AuditableMessage;
use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\Security\RoleRequirement;

/** @implements Command<null> */
final class DeleteProjectCommand implements Command, AuthorizedMessage, AuditableMessage
{
    public function __construct(
        public readonly string $id,
    ) {
    }

    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::user();
    }

    public function auditAction(): string
    {
        return 'project.deleted';
    }

    public function auditTargetId(): string
    {
        return $this->id;
    }

    public function auditContext(): array
    {
        return [];
    }
}
