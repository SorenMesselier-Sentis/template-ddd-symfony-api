<?php

declare(strict_types=1);

namespace App\User\Application\Command\RecordConsent;

use App\Shared\Domain\Audit\AuditableMessage;
use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\Security\RoleRequirement;
use App\User\Application\Security\AuthorizedMessage;

/** @implements Command<null> */
final class RecordConsentCommand implements Command, AuthorizedMessage, AuditableMessage
{
    public function __construct(
        public readonly string $type,
        public readonly string $version,
    ) {
    }

    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::authenticated();
    }

    public function auditAction(): string
    {
        return 'user.consent_recorded';
    }

    public function auditTargetId(): string
    {
        return $this->type;
    }

    public function auditContext(): array
    {
        return ['version' => $this->version];
    }
}
