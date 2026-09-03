<?php

declare(strict_types=1);

namespace App\Webhook\Infrastructure\EventHandler;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Infrastructure\Messaging\DomainEventPayloadExtractor;
use App\Webhook\Application\Command\DeliverWebhook\DeliverWebhookCommand;
use App\Webhook\Domain\Repository\WebhookSubscriptionRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Type-hinted to the abstract DomainEvent (Shared), not any one BC's event class, so Symfony
 * Messenger's handler resolution (which walks up the class hierarchy) invokes this for every
 * domain event published anywhere in the app — no per-BC integration needed. Only dispatches
 * DeliverWebhookCommand (cheap: one repository query, no I/O beyond that); the actual HTTP call
 * happens on its own transport, never here — see DeliverWebhookCommand's docblock for why.
 */
#[AsMessageHandler(bus: 'event.bus')]
final class DispatchWebhooksOnAnyDomainEvent
{
    public function __construct(
        private readonly WebhookSubscriptionRepositoryInterface $repository,
        private readonly CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(DomainEvent $event): void
    {
        $eventName = $event::eventName();
        $subscriptions = $this->repository->findActiveByEventName($eventName);

        if ([] === $subscriptions) {
            return;
        }

        $payload = DomainEventPayloadExtractor::extract($event);

        foreach ($subscriptions as $subscription) {
            $this->commandBus->dispatch(new DeliverWebhookCommand(
                subscriptionId: $subscription->id()->value(),
                eventId: $event->eventId(),
                eventName: $eventName,
                occurredOn: $event->occurredOn(),
                payload: $payload,
            ));
        }
    }
}
