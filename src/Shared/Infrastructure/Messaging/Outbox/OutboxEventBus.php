<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messaging\Outbox;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Infrastructure\Messaging\DomainEventPayloadExtractor;
use Doctrine\DBAL\Connection;

final class OutboxEventBus implements EventBusInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function publish(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            $this->connection->insert('outbox_messages', [
                'id' => $event->eventId(),
                'event_name' => $event::eventName(),
                'event_class' => $event::class,
                'aggregate_id' => $event->aggregateId(),
                'payload' => json_encode(DomainEventPayloadExtractor::extract($event), JSON_THROW_ON_ERROR),
                'occurred_on' => $event->occurredOn(),
                'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'published_at' => null,
            ]);
        }
    }
}
