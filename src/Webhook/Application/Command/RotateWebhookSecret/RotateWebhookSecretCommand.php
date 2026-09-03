<?php

declare(strict_types=1);

namespace App\Webhook\Application\Command\RotateWebhookSecret;

use App\Shared\Domain\Audit\AuditableMessage;
use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\Security\RoleRequirement;
use App\Webhook\Application\Security\AuthorizedMessage;

/** @implements Command<array{secret: string}> */
final class RotateWebhookSecretCommand implements Command, AuthorizedMessage, AuditableMessage
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
        return 'webhook_subscription.secret_rotated';
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
