<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Scheduler;

use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Domain\Monitoring\MetricsCollectorInterface;
use App\Shared\Infrastructure\Scheduler\Message\CleanupExpiredRefreshTokens;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Repository\RefreshTokenRepositoryInterface;
use App\User\Infrastructure\Scheduler\CleanupExpiredRefreshTokensHandler;

final class CleanupExpiredRefreshTokensHandlerTest extends UnitTestCase
{
    public function testItTracksSuccessfulRun(): void
    {
        $repository = $this->createMock(RefreshTokenRepositoryInterface::class);
        $repository->expects($this->once())->method('deleteExpired')->willReturn(5);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info')->with(
            'Scheduled refresh-token cleanup completed',
            ['deleted' => 5],
        );

        $metrics = $this->createMock(MetricsCollectorInterface::class);
        $metrics
            ->expects($this->once())
            ->method('incrementCounter')
            ->with('scheduler_task_runs_total', ['task' => 'cleanup_refresh_tokens', 'status' => 'ok']);

        $handler = new CleanupExpiredRefreshTokensHandler($repository, $logger, $metrics);
        $handler(new CleanupExpiredRefreshTokens());
    }

    public function testItTracksFailedRunWithoutThrowing(): void
    {
        $repository = $this->createMock(RefreshTokenRepositoryInterface::class);
        $repository->expects($this->once())->method('deleteExpired')->willThrowException(new \RuntimeException('db down'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with(
            'Scheduled refresh-token cleanup failed',
            $this->callback(static fn (array $context): bool => ($context['exception'] ?? null) instanceof \RuntimeException
                && 'db down' === $context['exception']->getMessage()),
        );

        $metrics = $this->createMock(MetricsCollectorInterface::class);
        $metrics
            ->expects($this->once())
            ->method('incrementCounter')
            ->with('scheduler_task_runs_total', ['task' => 'cleanup_refresh_tokens', 'status' => 'error']);

        $handler = new CleanupExpiredRefreshTokensHandler($repository, $logger, $metrics);
        $handler(new CleanupExpiredRefreshTokens());
    }
}
