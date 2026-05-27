<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Health;

use App\Shared\Domain\Health\HealthCheckInterface;
use App\Shared\Domain\Health\HealthCheckResult;
use App\Shared\Domain\Health\HealthCheckStatus;

final class HealthCheckRegistry
{
    /** @var HealthCheckInterface[] */
    private array $checks = [];

    public function __construct(iterable $checks)
    {
        foreach ($checks as $check) {
            $this->checks[] = $check;
        }
    }

    public function run(): HealthCheckResult
    {
        $results = [];
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

            $results[$checkName] = $status->state()->value;
            $checksDetails[$checkName] = [
                'status' => $status->state()->value,
                'duration_ms' => $durationMs,
            ];

            if (null !== $status->detail()) {
                $checksDetails[$checkName]['detail'] = $this->truncateDetail($status->detail());
            }
        }

        return HealthCheckResult::fromChecks($results, $checksDetails);
    }

    private function truncateDetail(string $detail): string
    {
        return mb_substr($detail, 0, 200);
    }
}
