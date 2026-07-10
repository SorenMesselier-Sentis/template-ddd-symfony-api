<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Scheduler;

use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Domain\Monitoring\MetricsCollectorInterface;
use App\Shared\Infrastructure\Scheduler\Message\CleanupExpiredUserTokens;
use App\User\Domain\Repository\EmailVerificationTokenRepositoryInterface;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class CleanupExpiredUserTokensHandler
{
    public function __construct(
        private readonly PasswordResetTokenRepositoryInterface $passwordResetTokenRepository,
        private readonly EmailVerificationTokenRepositoryInterface $emailVerificationTokenRepository,
        private readonly LoggerInterface $logger,
        private readonly MetricsCollectorInterface $metrics,
    ) {
    }

    public function __invoke(CleanupExpiredUserTokens $message): void
    {
        try {
            $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
            $passwordResetDeleted = $this->passwordResetTokenRepository->deleteExpired($now);
            $emailVerificationDeleted = $this->emailVerificationTokenRepository->deleteExpired($now);

            $this->logger->info('Scheduled user-token cleanup completed', [
                'password_reset_deleted' => $passwordResetDeleted,
                'email_verification_deleted' => $emailVerificationDeleted,
            ]);
            $this->metrics->incrementCounter('scheduler_task_runs_total', [
                'task' => 'cleanup_user_tokens',
                'status' => 'ok',
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Scheduled user-token cleanup failed', [
                'exception' => $e->getMessage(),
            ]);
            $this->metrics->incrementCounter('scheduler_task_runs_total', [
                'task' => 'cleanup_user_tokens',
                'status' => 'error',
            ]);
        }
    }
}
