<?php

declare(strict_types=1);

namespace App\User\Application\Command\EraseMyData;

use App\Shared\Domain\Audit\AuditableMessage;
use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\Security\RoleRequirement;
use App\User\Application\Security\AuthorizedMessage;

/**
 * Self-service GDPR right to erasure (`DELETE /users/me`). Unlike other self-service commands
 * (ChangePassword, UpdateMe), this one implements AuditableMessage — it's an irreversible
 * account-erasure action, not a routine profile edit. AuditMessageMiddleware reads
 * auditTargetId() off this object, so the id must be resolved and passed in at construction
 * time (by the controller, via UserContextInterface) rather than looked up lazily inside the
 * handler the way ExportUserDataQuery does — queries are never audited (query.bus has no
 * AuditMessageMiddleware), so that pattern never had to solve this.
 *
 * @implements Command<null>
 */
final class EraseMyDataCommand implements Command, AuthorizedMessage, AuditableMessage
{
    public function __construct(
        public readonly string $id,
    ) {
    }

    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::authenticated();
    }

    public function auditAction(): string
    {
        return 'user.data_erased';
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
