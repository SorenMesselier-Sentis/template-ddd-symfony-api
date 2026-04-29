<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messaging\Outbox;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use Doctrine\DBAL\Connection;

final class OutboxEventBus implements EventBusInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function publish(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            $this->connection->insert('outbox_messages', [
                'id' => $event->eventId(),
                'event_name' => $event::eventName(),
                'event_class' => $event::class,
                'aggregate_id' => $event->aggregateId(),
                'payload' => json_encode($this->extractPayload($event), JSON_THROW_ON_ERROR),
                'occurred_on' => $event->occurredOn(),
                'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'published_at' => null,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function extractPayload(DomainEvent $event): array
    {
        $reflection = new \ReflectionObject($event);
        $payload = [];

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $payload[$property->getName()] = $property->getValue($event);
        }

        return $payload;
    }
}
