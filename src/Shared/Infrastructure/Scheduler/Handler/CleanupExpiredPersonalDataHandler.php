<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Scheduler\Handler;

use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Domain\Monitoring\MetricsCollectorInterface;
use App\Shared\Domain\Privacy\PersonalDataAnonymizerInterface;
use App\Shared\Infrastructure\Scheduler\Message\CleanupExpiredPersonalData;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class CleanupExpiredPersonalDataHandler
{
    public const int DEFAULT_RETENTION_DAYS = 30;

    /**
     * @param iterable<PersonalDataAnonymizerInterface> $anonymizers
     */
    public function __construct(
        private readonly iterable $anonymizers,
        private readonly LoggerInterface $logger,
        private readonly MetricsCollectorInterface $metrics,
        private readonly int $retentionDays,
    ) {
    }

    public function __invoke(CleanupExpiredPersonalData $message): void
    {
        $retentionDays = $this->resolveRetention($this->retentionDays);
        $cutoff = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
            ->sub(new \DateInterval(sprintf('P%dD', $retentionDays)));

        foreach ($this->anonymizers as $anonymizer) {
            try {
                $anonymized = $anonymizer->anonymizeExpired($cutoff);

                $this->logger->info('Scheduled GDPR retention cleanup completed', [
                    'anonymizer' => $anonymizer->key(),
                    'anonymized' => $anonymized,
                    'retentionDays' => $retentionDays,
                ]);
                $this->metrics->incrementCounter('scheduler_task_runs_total', [
                    'task' => 'cleanup_expired_personal_data',
                    'status' => 'ok',
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Scheduled GDPR retention cleanup failed', [
                    'anonymizer' => $anonymizer->key(),
                    'exception' => $e,
                ]);
                $this->metrics->incrementCounter('scheduler_task_runs_total', [
                    'task' => 'cleanup_expired_personal_data',
                    'status' => 'error',
                ]);
            }
        }
    }

    private function resolveRetention(int $configured): int
    {
        if ($configured < 1) {
            $this->logger->warning('Invalid GDPR_RETENTION_DAYS, falling back to default', [
                'configured' => $configured,
                'default' => self::DEFAULT_RETENTION_DAYS,
            ]);

            return self::DEFAULT_RETENTION_DAYS;
        }

        return $configured;
    }
}
