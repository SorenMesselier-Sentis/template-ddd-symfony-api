<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Health;

use App\Shared\Domain\Health\HealthCheckInterface;
use App\Shared\Domain\Health\HealthCheckResult;
use App\Shared\Domain\Health\HealthCheckStatus;

final class HealthCheckRegistry
{
    private const DEBUG_LOG_PATH = '/Users/taranis/Developer/Project/Templates/template-ddd-symfony/.cursor/debug-170cf4.log';
    private const DEBUG_SESSION_ID = '170cf4';
    private const DEBUG_RUN_ID = 'initial';


    /** @var HealthCheckInterface[] */
    private array $checks = [];

    public function __construct(iterable $checks)
    {
        // #region agent log
        $this->debugLog(
            hypothesisId: 'H1',
            location: 'HealthCheckRegistry.php:24',
            message: 'Registry constructor started',
            data: ['checksType' => get_debug_type($checks)],
        );
        // #endregion

        foreach($checks as $check) {
            // #region agent log
            $this->debugLog(
                hypothesisId: 'H2',
                location: 'HealthCheckRegistry.php:34',
                message: 'Resolved tagged health check',
                data: ['resolvedType' => get_debug_type($check)],
            );
            // #endregion
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
                $status = HealthCheckStatus::error($e->getMessage());
            }

            $checkName = $check->name();
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            $results[$checkName] = $status->state()->value;
            $checksDetails[$checkName] = [
                'status' => $status->state()->value,
                'duration_ms' => $durationMs,
            ];

            if ($status->detail() !== null) {
                $checksDetails[$checkName]['detail'] = $status->detail();
            }
        }

        return HealthCheckResult::fromChecks($results, $checksDetails);
    }

    private function debugLog(string $hypothesisId, string $location, string $message, array $data = []): void
    {
        @file_put_contents(
            self::DEBUG_LOG_PATH,
            (string) json_encode([
                'sessionId' => self::DEBUG_SESSION_ID,
                'runId' => self::DEBUG_RUN_ID,
                'hypothesisId' => $hypothesisId,
                'location' => $location,
                'message' => $message,
                'data' => $data,
                'timestamp' => (int) (microtime(true) * 1000),
            ]) . PHP_EOL,
            FILE_APPEND,
        );
    }
}