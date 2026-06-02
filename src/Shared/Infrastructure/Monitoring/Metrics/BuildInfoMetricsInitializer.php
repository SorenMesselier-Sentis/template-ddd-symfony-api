<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Monitoring\Metrics;

use App\Shared\Domain\Monitoring\MetricsCollectorInterface;

final class BuildInfoMetricsInitializer
{
    private bool $initialized = false;

    public function __construct(
        private readonly MetricsCollectorInterface $metrics,
        private readonly string $appVersion,
        private readonly string $appEnv,
    ) {
    }

    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->metrics->setGauge('app_build_info', 1.0, [
            'version' => $this->appVersion,
            'php_version' => PHP_VERSION,
            'env' => $this->appEnv,
        ]);

        $this->initialized = true;
    }
}
