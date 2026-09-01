<?php

declare(strict_types=1);

namespace App\ApiClient\Application\Command\CreateApiClient;

use App\ApiClient\Application\Security\AuthorizedMessage;
use App\Shared\Domain\Audit\AuditableMessage;
use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\Security\RoleRequirement;

/** @implements Command<array{id: string, secret: string}> */
final class CreateApiClientCommand implements Command, AuthorizedMessage, AuditableMessage
{
    /**
     * @param list<string> $scopes
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly array $scopes,
    ) {
    }

    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::admin();
    }

    public function auditAction(): string
    {
        return 'api_client.created';
    }

    public function auditTargetId(): string
    {
        return $this->id;
    }

    public function auditContext(): array
    {
        return ['name' => $this->name, 'scopes' => $this->scopes];
    }
}
