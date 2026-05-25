<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Scheduler;

use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Infrastructure\Scheduler\Message\CleanupExpiredRefreshTokens;
use App\User\Domain\Repository\RefreshTokenRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class CleanupExpiredRefreshTokensHandler
{
    public function __construct(
        private readonly RefreshTokenRepositoryInterface $repository,
        private readonly LoggerInterface $logger,
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
        } catch (\Throwable $e) {
            $this->logger->error('Scheduled refresh-token cleanup failed', [
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
