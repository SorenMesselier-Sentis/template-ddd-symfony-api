<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Scheduler\Handler;

use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Domain\Monitoring\MetricsCollectorInterface;
use App\Shared\Infrastructure\Messaging\Outbox\OutboxMessagesCleaner;
use App\Shared\Infrastructure\Scheduler\Handler\CleanupStaleOutboxMessagesHandler;
use App\Shared\Infrastructure\Scheduler\Message\CleanupStaleOutboxMessages;
use App\Tests\Unit\UnitTestCase;
use Doctrine\DBAL\Connection;

final class CleanupStaleOutboxMessagesHandlerTest extends UnitTestCase
{
    public function testItTracksSuccessfulRun(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('executeStatement')->willReturn(4);
        $cleaner = new OutboxMessagesCleaner(
            $connection,
            $this->createStub(LoggerInterface::class),
            $this->createStub(MetricsCollectorInterface::class),
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info')->with(
            'Scheduled outbox cleanup completed',
            ['deleted' => 4, 'retentionDays' => 15],
        );

        $metrics = $this->createMock(MetricsCollectorInterface::class);
        $metrics
            ->expects($this->once())
            ->method('incrementCounter')
            ->with('scheduler_task_runs_total', ['task' => 'cleanup_stale_outbox', 'status' => 'ok']);

        $handler = new CleanupStaleOutboxMessagesHandler($cleaner, $logger, $metrics, 15);
        $handler(new CleanupStaleOutboxMessages());
    }

    public function testItTracksFailedRunWithoutThrowing(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('executeStatement')
            ->willThrowException(new \RuntimeException('db down'));
        $cleaner = new OutboxMessagesCleaner(
            $connection,
            $this->createStub(LoggerInterface::class),
            $this->createStub(MetricsCollectorInterface::class),
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with(
            'Scheduled outbox cleanup failed',
            ['exception' => 'db down'],
        );

        $metrics = $this->createMock(MetricsCollectorInterface::class);
        $metrics
            ->expects($this->once())
            ->method('incrementCounter')
            ->with('scheduler_task_runs_total', ['task' => 'cleanup_stale_outbox', 'status' => 'error']);

        $handler = new CleanupStaleOutboxMessagesHandler($cleaner, $logger, $metrics, 15);
        $handler(new CleanupStaleOutboxMessages());
    }
}
