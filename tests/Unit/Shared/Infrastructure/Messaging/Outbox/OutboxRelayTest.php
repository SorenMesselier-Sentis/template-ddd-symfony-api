<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Messaging\Outbox;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Domain\Monitoring\MetricsCollectorInterface;
use App\Shared\Infrastructure\Messaging\Outbox\OutboxRelay;
use App\Tests\Unit\UnitTestCase;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class OutboxRelayTest extends UnitTestCase
{
    public function testItRelaysOutboxRowsAndIncrementsMetrics(): void
    {
        $connection = $this->createMock(Connection::class);
        $eventBus = $this->createMock(MessageBusInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $metrics = $this->createMock(MetricsCollectorInterface::class);

        $connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn([[
                'id' => 'outbox-1',
                'event_class' => DummyOutboxEvent::class,
                'aggregate_id' => 'user-1',
                'payload' => json_encode(['payloadValue' => 'hello'], JSON_THROW_ON_ERROR),
            ]]);

        $eventBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static function (DomainEvent $event): bool {
                return $event instanceof DummyOutboxEvent
                    && 'user-1' === $event->aggregateId()
                    && 'hello' === $event->payloadValue;
            }))
            ->willReturn(new Envelope(new \stdClass()));

        $connection
            ->expects($this->once())
            ->method('update')
            ->with(
                'outbox_messages',
                $this->arrayHasKey('published_at'),
                ['id' => 'outbox-1'],
            );

        $metrics
            ->expects($this->once())
            ->method('incrementCounter')
            ->with('outbox_messages_relayed_total');

        $logger
            ->expects($this->once())
            ->method('info')
            ->with('Outbox relay published events', ['count' => 1]);

        $relay = new OutboxRelay($connection, $eventBus, $logger, $metrics);

        $published = $relay->relay(limit: 10);

        $this->assertSame(1, $published);
    }

    public function testItCountsUnpublishedMessages(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchOne')
            ->with('SELECT COUNT(*) FROM outbox_messages WHERE published_at IS NULL')
            ->willReturn('42');

        $relay = new OutboxRelay(
            $connection,
            $this->createStub(MessageBusInterface::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(MetricsCollectorInterface::class),
        );

        $this->assertSame(42, $relay->countUnpublishedMessages());
    }
}

final class DummyOutboxEvent extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        public readonly string $payloadValue,
    ) {
        parent::__construct($aggregateId);
    }

    public static function eventName(): string
    {
        return 'dummy.outbox';
    }
}
