<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Scheduler;

use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Domain\Monitoring\MetricsCollectorInterface;
use App\Shared\Infrastructure\Scheduler\Message\CleanupExpiredRefreshTokens;
use App\User\Domain\Repository\RefreshTokenRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class CleanupExpiredRefreshTokensHandler
{
    public function __construct(
        private readonly RefreshTokenRepositoryInterface $repository,
        private readonly LoggerInterface $logger,
        private readonly MetricsCollectorInterface $metrics,
    ) {
    }

    public function __invoke(CleanupExpiredRefreshTokens $message): void
    {
        try {
            $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
            $deleted = $this->repository->deleteExpired($now);

            $this->logger->info('Scheduled refresh-token cleanup completed', [
                'deleted' => $deleted,
            ]);
            $this->metrics->incrementCounter('scheduler_task_runs_total', [
                'task' => 'cleanup_refresh_tokens',
                'status' => 'ok',
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Scheduled refresh-token cleanup failed', [
                'exception' => $e->getMessage(),
            ]);
            $this->metrics->incrementCounter('scheduler_task_runs_total', [
                'task' => 'cleanup_refresh_tokens',
                'status' => 'error',
            ]);
        }
    }
}
