<?php

declare(strict_types=1);

namespace App\Shared\Domain\Health;

final class HealthCheckResult
{
    /**
     * @param array<string, string>            $checks
     * @param array<string, HealthCheckDetail> $checksDetails
     */
    public function __construct(
        public readonly HealthCheckStatus $status,
        public readonly array $checks,
        public readonly array $checksDetails = [],
    ) {
    }

    /**
     * @param array<string, string>            $checks
     * @param array<string, HealthCheckDetail> $checksDetails
     */
    public static function fromChecks(array $checks, array $checksDetails = []): self
    {
        $hasError = in_array('error', $checks, true);

        return new self(
            status: $hasError ? HealthCheckStatus::error('error') : HealthCheckStatus::ok('ok'),
            checks: $checks,
            checksDetails: $checksDetails,
        );
    }

    public function isHealthy(): bool
    {
        return $this->status->isOk();
    }

    public function httpStatusCode(): int
    {
        return $this->isHealthy() ? 200 : 503;
    }
}
