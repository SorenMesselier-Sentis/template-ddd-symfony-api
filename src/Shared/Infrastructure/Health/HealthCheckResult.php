<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Health;

final readonly class HealthCheckResult
{
    /**
     * @param array<string, string> $checks
     */
    public function __construct(
        public string $status,
        public array $checks,
    ) {
    }

    public function isHealthy(): bool
    {
        return 'ok' === $this->status;
    }

    public function httpStatusCode(): int
    {
        return $this->isHealthy() ? 200 : 503;
    }
}
