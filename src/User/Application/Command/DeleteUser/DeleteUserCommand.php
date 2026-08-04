<?php

declare(strict_types=1);

namespace App\User\Application\Command\DeleteUser;

use App\Shared\Domain\Audit\AuditableMessage;
use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\Security\RoleRequirement;
use App\User\Application\Security\AuthorizedMessage;

/** @implements Command<null> */
final class DeleteUserCommand implements Command, AuthorizedMessage, AuditableMessage
{
    public function __construct(
        public readonly string $id,
    ) {
    }

    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::admin();
    }

    public function auditAction(): string
    {
        return 'user.deleted';
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
