<?php

declare(strict_types=1);

namespace App\Shared\Domain\Audit;

/**
 * Implemented by commands whose successful execution must be recorded in the
 * audit trail (e.g. role changes, deletions, logins) — declared on the
 * message itself, the same way authorization is (see AuthorizedMessageContract).
 */
interface AuditableMessage
{
    public function auditAction(): string;

    public function auditTargetId(): string;

    /**
     * @return array<string, mixed>
     */
    public function auditContext(): array;
}
