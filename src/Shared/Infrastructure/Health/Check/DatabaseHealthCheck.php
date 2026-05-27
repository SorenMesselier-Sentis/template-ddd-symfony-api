<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Health\Check;

use App\Shared\Domain\Health\HealthCheckInterface;
use App\Shared\Domain\Health\HealthCheckStatus;
use Doctrine\DBAL\Connection;

final class DatabaseHealthCheck implements HealthCheckInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function name(): string
    {
        return 'database';
    }

    public function check(): HealthCheckStatus
    {
        try {
            $this->connection->executeQuery($this->connection->getDatabasePlatform()->getDummySelectSQL());

            return HealthCheckStatus::ok();
        } catch (\Throwable $e) {
            return HealthCheckStatus::error($e->getMessage());
        }
    }
}
