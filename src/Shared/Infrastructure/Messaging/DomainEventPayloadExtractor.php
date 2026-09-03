<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messaging;

use App\Shared\Domain\Bus\Event\DomainEvent;

/**
 * Reflects over a domain event's own public properties (never the base DomainEvent class's
 * private aggregateId/eventId/occurredOn — callers read those through the event's own getters
 * instead) into a generic, event-agnostic JSON-safe array. Originally inlined in OutboxEventBus;
 * extracted here once a second consumer (Webhook\Infrastructure\EventHandler\
 * DispatchWebhooksOnAnyDomainEvent) needed the exact same thing.
 */
final class DomainEventPayloadExtractor
{
    /**
     * @return array<string, mixed>
     */
    public static function extract(DomainEvent $event): array
    {
        $reflection = new \ReflectionObject($event);
        $payload = [];

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $value = $property->getValue($event);
            $payload[$property->getName()] = $value instanceof \BackedEnum ? $value->value : $value;
        }

        return $payload;
    }
}
