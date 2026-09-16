<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Scheduler\Handler;

use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Domain\Monitoring\MetricsCollectorInterface;
use App\Shared\Domain\Privacy\PersonalDataAnonymizerInterface;
use App\Shared\Infrastructure\Scheduler\Handler\CleanupExpiredPersonalDataHandler;
use App\Shared\Infrastructure\Scheduler\Message\CleanupExpiredPersonalData;
use App\Tests\Unit\UnitTestCase;

final class CleanupExpiredPersonalDataHandlerTest extends UnitTestCase
{
    public function testItTracksSuccessfulRunPerAnonymizer(): void
    {
        $anonymizer = $this->createMock(PersonalDataAnonymizerInterface::class);
        $anonymizer->method('key')->willReturn('user');
        $anonymizer->expects($this->once())->method('anonymizeExpired')->willReturn(3);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info')->with(
            'Scheduled GDPR retention cleanup completed',
            ['anonymizer' => 'user', 'anonymized' => 3, 'retentionDays' => 30],
        );

        $metrics = $this->createMock(MetricsCollectorInterface::class);
        $metrics
            ->expects($this->once())
            ->method('incrementCounter')
            ->with('scheduler_task_runs_total', ['task' => 'cleanup_expired_personal_data', 'status' => 'ok']);

        $handler = new CleanupExpiredPersonalDataHandler([$anonymizer], $logger, $metrics, 30);
        $handler(new CleanupExpiredPersonalData());
    }

    public function testItTracksFailedRunWithoutThrowing(): void
    {
        $anonymizer = $this->createMock(PersonalDataAnonymizerInterface::class);
        $anonymizer->method('key')->willReturn('user');
        $anonymizer->expects($this->once())->method('anonymizeExpired')->willThrowException(new \RuntimeException('db down'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with(
            'Scheduled GDPR retention cleanup failed',
            $this->callback(static fn (array $context): bool => 'user' === $context['anonymizer']
                && ($context['exception'] ?? null) instanceof \RuntimeException),
        );

        $metrics = $this->createMock(MetricsCollectorInterface::class);
        $metrics
            ->expects($this->once())
            ->method('incrementCounter')
            ->with('scheduler_task_runs_total', ['task' => 'cleanup_expired_personal_data', 'status' => 'error']);

        $handler = new CleanupExpiredPersonalDataHandler([$anonymizer], $logger, $metrics, 30);
        $handler(new CleanupExpiredPersonalData());
    }

    public function testItFallsBackToDefaultRetentionWhenInvalid(): void
    {
        $anonymizer = $this->createMock(PersonalDataAnonymizerInterface::class);
        $anonymizer->method('key')->willReturn('user');
        $anonymizer->expects($this->once())->method('anonymizeExpired')->willReturn(0);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning');
        $logger->expects($this->once())->method('info')->with(
            'Scheduled GDPR retention cleanup completed',
            ['anonymizer' => 'user', 'anonymized' => 0, 'retentionDays' => CleanupExpiredPersonalDataHandler::DEFAULT_RETENTION_DAYS],
        );

        $handler = new CleanupExpiredPersonalDataHandler(
            [$anonymizer],
            $logger,
            $this->createStub(MetricsCollectorInterface::class),
            0,
        );
        $handler(new CleanupExpiredPersonalData());
    }
}
