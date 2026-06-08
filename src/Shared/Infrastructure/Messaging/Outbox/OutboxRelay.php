<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messaging\Outbox;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Domain\Monitoring\MetricsCollectorInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\Messenger\MessageBusInterface;

final class OutboxRelay
{
    public function __construct(
        private readonly Connection $connection,
        private readonly MessageBusInterface $eventBus,
        private readonly LoggerInterface $logger,
        private readonly MetricsCollectorInterface $metrics,
    ) {
    }

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
            $this->metrics->incrementCounter('outbox_messages_relayed_total');

            ++$published;
        }

        if ($published > 0) {
            $this->logger->info('Outbox relay published events', ['count' => $published]);
        }

        return $published;
    }

    public function countUnpublishedMessages(): int
    {
        $count = $this->connection->fetchOne('SELECT COUNT(*) FROM outbox_messages WHERE published_at IS NULL');

        return (int) $count;
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
                throw new \RuntimeException(sprintf('Missing outbox payload key "%s" for "%s".', $name, $eventClass));
            }

            $arguments[] = $this->resolveConstructorArgument($parameter, $payload[$name]);
        }

        $event = $reflection->newInstanceArgs($arguments);

        if (!$event instanceof DomainEvent) {
            throw new \RuntimeException(sprintf('Class "%s" must implement %s.', $eventClass, DomainEvent::class));
        }

        return $event;
    }

    private function resolveConstructorArgument(\ReflectionParameter $parameter, mixed $value): mixed
    {
        $type = $parameter->getType();

        if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
            return $value;
        }

        $className = $type->getName();

        if (is_subclass_of($className, \BackedEnum::class)) {
            if (!\is_string($value) && !\is_int($value)) {
                throw new \RuntimeException(sprintf('Cannot hydrate enum "%s" from value of type "%s".', $className, \get_debug_type($value)));
            }

            /* @var class-string<\BackedEnum> $className */
            return $className::from($value);
        }

        return $value;
    }
}
