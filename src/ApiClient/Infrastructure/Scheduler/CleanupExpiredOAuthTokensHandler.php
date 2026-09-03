<?php

declare(strict_types=1);

namespace App\ApiClient\Infrastructure\Scheduler;

use App\ApiClient\Domain\Repository\IssuedAccessTokenRepositoryInterface;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Domain\Monitoring\MetricsCollectorInterface;
use App\Shared\Infrastructure\Scheduler\Message\CleanupExpiredOAuthTokens;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class CleanupExpiredOAuthTokensHandler
{
    public function __construct(
        private readonly IssuedAccessTokenRepositoryInterface $repository,
        private readonly LoggerInterface $logger,
        private readonly MetricsCollectorInterface $metrics,
    ) {
    }

    public function __invoke(CleanupExpiredOAuthTokens $message): void
    {
        try {
            $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
            $deleted = $this->repository->deleteExpired($now);

            $this->logger->info('Scheduled OAuth2 access-token cleanup completed', [
                'deleted' => $deleted,
            ]);
            $this->metrics->incrementCounter('scheduler_task_runs_total', [
                'task' => 'cleanup_oauth_tokens',
                'status' => 'ok',
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Scheduled OAuth2 access-token cleanup failed', [
                'exception' => $e,
            ]);
            $this->metrics->incrementCounter('scheduler_task_runs_total', [
                'task' => 'cleanup_oauth_tokens',
                'status' => 'error',
            ]);
        }
    }
}
