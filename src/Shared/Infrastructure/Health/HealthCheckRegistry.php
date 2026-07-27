<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Health;

use App\Shared\Domain\Health\HealthCheckDetail;
use App\Shared\Domain\Health\HealthCheckInterface;
use App\Shared\Domain\Health\HealthCheckResult;
use App\Shared\Domain\Health\HealthCheckStatus;

final class HealthCheckRegistry
{
    /** @var list<HealthCheckInterface> */
    private array $checks = [];

    /**
     * @param iterable<HealthCheckInterface> $checks
     */
    public function __construct(iterable $checks)
    {
        foreach ($checks as $check) {
            $this->checks[] = $check;
        }
    }

    public function run(): HealthCheckResult
    {
        /** @var array<string, string> $results */
        $results = [];
        /** @var array<string, HealthCheckDetail> $checksDetails */
        $checksDetails = [];

        foreach ($this->checks as $check) {
            $startedAt = microtime(true);

            try {
                $status = $check->check();
            } catch (\Throwable $e) {
                $status = HealthCheckStatus::error($this->truncateDetail($e->getMessage()));
            }

            $checkName = $check->name();
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
            $detail = $status->detail();

            $results[$checkName] = $status->state()->value;
            $checksDetails[$checkName] = new HealthCheckDetail(
                status: $status->state()->value,
                durationMs: $durationMs,
                detail: null !== $detail ? $this->truncateDetail($detail) : null,
            );
        }

        return HealthCheckResult::fromChecks($results, $checksDetails);
    }

    private function truncateDetail(string $detail): string
    {
        return mb_substr($detail, 0, 200);
    }
}
