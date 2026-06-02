<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Scheduler\Handler;

use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Domain\Monitoring\MetricsCollectorInterface;
use App\Shared\Infrastructure\Messaging\Outbox\OutboxRelay;
use App\Shared\Infrastructure\Scheduler\Message\RelayOutboxMessages;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class RelayOutboxMessagesHandler
{
    public function __construct(
        private readonly OutboxRelay $outboxRelay,
        private readonly LoggerInterface $logger,
        private readonly MetricsCollectorInterface $metrics,
    ) {
    }

    public function __invoke(RelayOutboxMessages $message): void
    {
        $this->metrics->setGauge(
            'outbox_unpublished_messages',
            $this->outboxRelay->countUnpublishedMessages(),
        );

        try {
            $this->outboxRelay->relay();
            $this->metrics->incrementCounter('scheduler_task_runs_total', [
                'task' => 'relay_outbox',
                'status' => 'ok',
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Scheduled outbox relay failed', [
                'exception' => $e->getMessage(),
            ]);
            $this->metrics->incrementCounter('scheduler_task_runs_total', [
                'task' => 'relay_outbox',
                'status' => 'error',
            ]);
        }
    }
}
