<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Messaging\Outbox;

use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Domain\Monitoring\MetricsCollectorInterface;
use App\Shared\Infrastructure\Messaging\Outbox\OutboxMessagesCleaner;
use App\Tests\Unit\UnitTestCase;
use Doctrine\DBAL\Connection;

final class OutboxMessagesCleanerTest extends UnitTestCase
{
    public function testItEmitsPurgeMetricWithConfiguredRetention(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('executeStatement')
            ->willReturn(3);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('warning');

        $metrics = $this->createMock(MetricsCollectorInterface::class);
        $metrics
            ->expects($this->once())
            ->method('incrementCounter')
            ->with('outbox_messages_purged_total', ['retention_days' => 7]);

        $cleaner = new OutboxMessagesCleaner($connection, $logger, $metrics);

        $deleted = $cleaner->purge(7, new \DateTimeImmutable('2026-06-02T00:00:00+00:00'));

        $this->assertSame(3, $deleted);
    }

    public function testItEmitsPurgeMetricWithFallbackRetention(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('executeStatement')
            ->willReturn(0);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning');

        $metrics = $this->createMock(MetricsCollectorInterface::class);
        $metrics
            ->expects($this->once())
            ->method('incrementCounter')
            ->with('outbox_messages_purged_total', [
                'retention_days' => OutboxMessagesCleaner::DEFAULT_RETENTION_DAYS,
            ]);

        $cleaner = new OutboxMessagesCleaner($connection, $logger, $metrics);

        $cleaner->purge(0, new \DateTimeImmutable('2026-06-02T00:00:00+00:00'));
    }
}
