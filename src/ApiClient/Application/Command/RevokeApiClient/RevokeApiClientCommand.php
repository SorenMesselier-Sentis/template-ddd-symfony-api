<?php

declare(strict_types=1);

namespace App\ApiClient\Application\Command\RevokeApiClient;

use App\ApiClient\Application\Security\AuthorizedMessage;
use App\Shared\Domain\Audit\AuditableMessage;
use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\Security\RoleRequirement;

/** @implements Command<null> */
final class RevokeApiClientCommand implements Command, AuthorizedMessage, AuditableMessage
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
        return 'api_client.revoked';
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
