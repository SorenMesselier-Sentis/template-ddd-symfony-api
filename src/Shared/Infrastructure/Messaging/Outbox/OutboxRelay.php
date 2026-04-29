<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messaging\Outbox;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Logging\LoggerInterface;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\MessageBusInterface;

final class OutboxRelay
{
    public function __construct(
        private readonly Connection $connection,
        private readonly MessageBusInterface $eventBus,
        private readonly LoggerInterface $logger,
    ) {}

    public function relay(int $limit = 100): int
    {
        $messages = $this->connection->fetchAllAssociative(
            'SELECT id, event_class, aggregate_id, payload FROM outbox_messages WHERE published_at IS NULL ORDER BY created_at ASC LIMIT ?',
            [$limit],
            [\PDO::PARAM_INT]
        );

        $published = 0;
        foreach ($messages as $message) {
            $event = $this->rehydrateEvent(
                eventClass: (string) $message['event_class'],
                aggregateId: (string) $message['aggregate_id'],
                payloadJson: (string) $message['payload'],
            );

            $this->eventBus->dispatch($event);
            $this->connection->update('outbox_messages', [
                'published_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ], [
                'id' => (string) $message['id'],
            ]);

            $published++;
        }

        if ($published > 0) {
            $this->logger->info('Outbox relay published events', ['count' => $published]);
        }

        return $published;
    }

    private function rehydrateEvent(string $eventClass, string $aggregateId, string $payloadJson): DomainEvent
    {
        /** @var array<string, mixed> $payload */
        $payload = json_decode($payloadJson, true, 512, JSON_THROW_ON_ERROR);
        $reflection = new \ReflectionClass($eventClass);
        $constructor = $reflection->getConstructor();

        if (null === $constructor) {
            return $reflection->newInstance();
        }

        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();

            if ('aggregateId' === $name) {
                $arguments[] = $aggregateId;
                continue;
            }

            if (!array_key_exists($name, $payload)) {
                throw new \RuntimeException(sprintf('Missing outbox payload key "%s" for "%s".', $name, $eventClass));
            }

            $arguments[] = $payload[$name];
        }

        /** @var DomainEvent $event */
        $event = $reflection->newInstanceArgs($arguments);

        return $event;
    }
}
