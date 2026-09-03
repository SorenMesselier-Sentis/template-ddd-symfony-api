<?php

declare(strict_types=1);

namespace App\Webhook\Application\Command\UpdateWebhookSubscription;

use App\Shared\Domain\Audit\AuditableMessage;
use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\Security\RoleRequirement;
use App\Webhook\Application\Security\AuthorizedMessage;

/** @implements Command<null> */
final class UpdateWebhookSubscriptionCommand implements Command, AuthorizedMessage, AuditableMessage
{
    /**
     * @param list<string> $eventNames
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $url,
        public readonly array $eventNames,
    ) {
    }

    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::admin();
    }

    public function auditAction(): string
    {
        return 'webhook_subscription.updated';
    }

    public function auditTargetId(): string
    {
        return $this->id;
    }

    public function auditContext(): array
    {
        return ['name' => $this->name, 'url' => $this->url, 'event_names' => $this->eventNames];
    }
}
