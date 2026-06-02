<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messaging\Outbox;

use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Domain\Monitoring\MetricsCollectorInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

final class OutboxMessagesCleaner
{
    public const int DEFAULT_RETENTION_DAYS = 30;

    public function __construct(
        private readonly Connection $connection,
        private readonly LoggerInterface $logger,
        private readonly MetricsCollectorInterface $metrics,
    ) {
    }

    /**
     * Deletes every `outbox_messages` row that has been published more than
     * `$retentionDays` days before `$now`.
     *
     * If the configured retention is less than 1 day, the cleaner falls back to
     * {@see self::DEFAULT_RETENTION_DAYS} and logs a warning, so a misconfiguration
     * is surfaced operationally without crashing the worker.
     *
     * @return int number of rows deleted
     */
    public function purge(int $retentionDays, \DateTimeImmutable $now): int
    {
        $effectiveRetention = $this->resolveRetention($retentionDays);
        $cutoff = $now->sub(new \DateInterval(\sprintf('P%dD', $effectiveRetention)));

        $deleted = $this->connection->executeStatement(
            'DELETE FROM outbox_messages WHERE published_at IS NOT NULL AND published_at < ?',
            [$cutoff->format('Y-m-d H:i:s')],
            [ParameterType::STRING],
        );
        $this->metrics->incrementCounter('outbox_messages_purged_total', [
            'retention_days' => $effectiveRetention,
        ]);

        return (int) $deleted;
    }

    private function resolveRetention(int $configured): int
    {
        if ($configured < 1) {
            $this->logger->warning('Invalid OUTBOX_RETENTION_DAYS, falling back to default', [
                'configured' => $configured,
                'default' => self::DEFAULT_RETENTION_DAYS,
            ]);

            return self::DEFAULT_RETENTION_DAYS;
        }

        return $configured;
    }
}
