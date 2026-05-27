<?php

declare(strict_types=1);

namespace App\Shared\Domain\Health;

final class HealthCheckResult
{
    public function __construct(
        public readonly HealthCheckStatus $status,
        public readonly array $checks,
    ) {
    }

    public static function fromChecks(array $checks): self
    {
        $hasError = in_array('error', $checks, true);

        return new self(
            status: $hasError ? HealthCheckStatus::error('error') : HealthCheckStatus::ok('ok'),
            checks: $checks,
        );
    }

    public function isHealthy(): bool
    {
        return $this->status->isOk();
    }

    public function httpStatusCode(): int
    {
        return $this->isHealthy() ? 200 : 500;
    }
}