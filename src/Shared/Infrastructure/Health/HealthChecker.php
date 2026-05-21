<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Health;

use Doctrine\DBAL\Connection;

final class HealthChecker
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function check(): HealthCheckResult
    {
        $checks = [
            'api' => 'ok',
        ];

        try {
            $this->connection->executeQuery(
                $this->connection->getDatabasePlatform()->getDummySelectSQL(),
            );
            $checks['database'] = 'ok';
        } catch (\Throwable) {
            $checks['database'] = 'error';

            return new HealthCheckResult(status: 'error', checks: $checks);
        }

        return new HealthCheckResult(status: 'ok', checks: $checks);
    }
}
