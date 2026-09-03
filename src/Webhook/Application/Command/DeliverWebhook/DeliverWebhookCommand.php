<?php

declare(strict_types=1);

namespace App\Webhook\Application\Command\DeliverWebhook;

use App\Shared\Domain\Bus\Command\Command;

/**
 * System-triggered, never dispatched by a human caller — deliberately does not implement
 * AuthorizedMessage/AuditableMessage (mirrors the scheduler's cleanup messages). Routed to its
 * own `webhook_delivery` transport (see messenger.yaml), not the default `sync`, so a slow/down
 * third party never blocks command.bus callers or the main event-processing worker — see
 * DispatchWebhooksOnAnyDomainEvent, which is the only thing that dispatches this.
 *
 * @implements Command<null>
 */
final class DeliverWebhookCommand implements Command
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly string $subscriptionId,
        public readonly string $eventId,
        public readonly string $eventName,
        public readonly string $occurredOn,
        public readonly array $payload,
    ) {
    }
}
