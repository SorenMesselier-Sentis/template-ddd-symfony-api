<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Scheduler\Handler;

use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Domain\Monitoring\MetricsCollectorInterface;
use App\Shared\Infrastructure\Messaging\Outbox\OutboxRelay;
use App\Shared\Infrastructure\Scheduler\Handler\RelayOutboxMessagesHandler;
use App\Shared\Infrastructure\Scheduler\Message\RelayOutboxMessages;
use App\Tests\Unit\UnitTestCase;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\MessageBusInterface;

final class RelayOutboxMessagesHandlerTest extends UnitTestCase
{
    public function testItRefreshesGaugeAndTracksSuccessfulRun(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('fetchOne')->willReturn('12');
        $connection->expects($this->once())->method('fetchAllAssociative')->willReturn([]);
        $relay = new OutboxRelay(
            $connection,
            $this->createStub(MessageBusInterface::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(MetricsCollectorInterface::class),
        );

        $metrics = $this->createMock(MetricsCollectorInterface::class);
        $metrics
            ->expects($this->once())
            ->method('setGauge')
            ->with('outbox_unpublished_messages', 12.0);
        $metrics
            ->expects($this->once())
            ->method('incrementCounter')
            ->with('scheduler_task_runs_total', ['task' => 'relay_outbox', 'status' => 'ok']);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $handler = new RelayOutboxMessagesHandler($relay, $logger, $metrics);
        $handler(new RelayOutboxMessages());
    }

    public function testItTracksFailedRunWithoutThrowing(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('fetchOne')->willReturn('3');
        $connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->willThrowException(new \RuntimeException('boom'));
        $relay = new OutboxRelay(
            $connection,
            $this->createStub(MessageBusInterface::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(MetricsCollectorInterface::class),
        );

        $metrics = $this->createMock(MetricsCollectorInterface::class);
        $metrics->expects($this->once())->method('setGauge')->with('outbox_unpublished_messages', 3.0);
        $metrics
            ->expects($this->once())
            ->method('incrementCounter')
            ->with('scheduler_task_runs_total', ['task' => 'relay_outbox', 'status' => 'error']);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with(
            'Scheduled outbox relay failed',
            $this->callback(static fn (array $context): bool => ($context['exception'] ?? null) instanceof \RuntimeException
                && 'boom' === $context['exception']->getMessage()),
        );

        $handler = new RelayOutboxMessagesHandler($relay, $logger, $metrics);
        $handler(new RelayOutboxMessages());
    }
}
