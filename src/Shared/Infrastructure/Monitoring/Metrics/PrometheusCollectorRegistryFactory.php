<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Monitoring\Metrics;

use Prometheus\CollectorRegistry;
use Prometheus\Storage\APC;
use Prometheus\Storage\Redis;

final class PrometheusCollectorRegistryFactory
{
    public function __construct(
        private readonly string $metricsStorage,
        private readonly string $redisHost = '127.0.0.1',
        private readonly int $redisPort = 6379,
        private readonly ?string $redisPassword = null,
    ) {
    }

    public function create(): CollectorRegistry
    {
        return new CollectorRegistry($this->createStorageAdapter());
    }

    private function createStorageAdapter(): APC|Redis
    {
        return match ($this->metricsStorage) {
            'apcu' => new APC(),
            'redis' => new Redis([
                'host' => $this->redisHost,
                'port' => $this->redisPort,
                'password' => '' !== $this->redisPassword && null !== $this->redisPassword
                    ? $this->redisPassword
                    : null,
            ]),
            default => throw new \LogicException(sprintf('Unsupported METRICS_STORAGE value "%s"; expected "apcu" or "redis".', $this->metricsStorage)),
        };
    }
}
