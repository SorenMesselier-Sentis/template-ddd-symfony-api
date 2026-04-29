<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messaging\Outbox;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Logging\LoggerInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
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
            [ParameterType::INTEGER]
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

        if (!class_exists($eventClass)) {
            throw new \RuntimeException(sprintf('Unknown event class "%s".', $eventClass));
        }

        /** @var class-string $eventClass */
        $reflection = new \ReflectionClass($eventClass);
        $constructor = $reflection->getConstructor();

        if (null === $constructor) {
            $event = $reflection->newInstance();

            if (!$event instanceof DomainEvent) {
                throw new \RuntimeException(sprintf('Class "%s" must implement %s.', $eventClass, DomainEvent::class));
            }

            return $event;
        }

        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();

            if ('aggregateId' === $name) {
                $arguments[] = $aggregateId;
                continue;
            }

            if (!array_key_exists($name, $payload)) {
                throw new \RuntimeException(sprintf(
                    'Missing outbox payload key "%s" for "%s".',
                    $name,
                    $eventClass
                ));
            }

            $arguments[] = $payload[$name];
        }

        $event = $reflection->newInstanceArgs($arguments);

        if (!$event instanceof DomainEvent) {
            throw new \RuntimeException(sprintf(
                'Class "%s" must implement %s.',
                $eventClass,
                DomainEvent::class
            ));
            }

        return $event;
    }
}
