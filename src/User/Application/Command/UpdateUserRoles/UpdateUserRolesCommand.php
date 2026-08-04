<?php

declare(strict_types=1);

namespace App\User\Application\Command\UpdateUserRoles;

use App\Shared\Domain\Audit\AuditableMessage;
use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\Security\RoleRequirement;
use App\User\Application\Security\AuthorizedMessage;

/** @implements Command<null> */
final class UpdateUserRolesCommand implements Command, AuthorizedMessage, AuditableMessage
{
    /**
     * @param list<string> $roles
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

    public function auditAction(): string
    {
        return 'user.roles_updated';
    }

    public function auditTargetId(): string
    {
        return $this->id;
    }

    public function auditContext(): array
    {
        return ['roles' => $this->roles];
    }
}
