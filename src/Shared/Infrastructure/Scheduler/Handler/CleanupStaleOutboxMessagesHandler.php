<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Scheduler\Handler;

use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Domain\Monitoring\MetricsCollectorInterface;
use App\Shared\Infrastructure\Messaging\Outbox\OutboxMessagesCleaner;
use App\Shared\Infrastructure\Scheduler\Message\CleanupStaleOutboxMessages;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class CleanupStaleOutboxMessagesHandler
{
    public function __construct(
        private readonly OutboxMessagesCleaner $cleaner,
        private readonly LoggerInterface $logger,
        private readonly MetricsCollectorInterface $metrics,
        private readonly int $retentionDays,
    ) {
    }

    public function __invoke(CleanupStaleOutboxMessages $message): void
    {
        try {
            $deleted = $this->cleaner->purge(
                retentionDays: $this->retentionDays,
                now: new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
            );

            $this->logger->info('Scheduled outbox cleanup completed', [
                'deleted' => $deleted,
                'retentionDays' => $this->retentionDays,
            ]);
            $this->metrics->incrementCounter('scheduler_task_runs_total', [
                'task' => 'cleanup_stale_outbox',
                'status' => 'ok',
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Scheduled outbox cleanup failed', [
                'exception' => $e->getMessage(),
            ]);
            $this->metrics->incrementCounter('scheduler_task_runs_total', [
                'task' => 'cleanup_stale_outbox',
                'status' => 'error',
            ]);
        }
    }
}
