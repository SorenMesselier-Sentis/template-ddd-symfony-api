<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Monitoring\Metrics;

use App\Shared\Domain\Monitoring\MetricsCollectorInterface;
use Prometheus\CollectorRegistry;

final class PrometheusMetricsCollector implements MetricsCollectorInterface
{
    private const string METRIC_NAMESPACE = '';

    private const string DEFAULT_HELP = 'Application metric.';

    public function __construct(
        private readonly CollectorRegistry $registry,
    ) {
    }

    public function incrementCounter(string $name, array $labels = [], float $value = 1.0): void
    {
        $labelNames = $this->labelNames($labels);

        $this->registry
            ->getOrRegisterCounter(self::METRIC_NAMESPACE, $name, self::DEFAULT_HELP, $labelNames)
            ->incBy($value, $this->labelValues($labels));
    }

    public function observeHistogram(string $name, float $value, array $labels = []): void
    {
        $labelNames = $this->labelNames($labels);

        $this->registry
            ->getOrRegisterHistogram(self::METRIC_NAMESPACE, $name, self::DEFAULT_HELP, $labelNames)
            ->observe($value, $this->labelValues($labels));
    }

    public function setGauge(string $name, float $value, array $labels = []): void
    {
        $labelNames = $this->labelNames($labels);

        $this->registry
            ->getOrRegisterGauge(self::METRIC_NAMESPACE, $name, self::DEFAULT_HELP, $labelNames)
            ->set($value, $this->labelValues($labels));
    }

    /**
     * @param array<string, string|int|float|bool> $labels
     *
     * @return list<string>
     */
    private function labelNames(array $labels): array
    {
        $names = array_keys($labels);
        sort($names, SORT_STRING);

        return $names;
    }

    /**
     * @param array<string, string|int|float|bool> $labels
     *
     * @return list<string>
     */
    private function labelValues(array $labels): array
    {
        $values = [];

        foreach ($this->labelNames($labels) as $name) {
            $values[] = (string) $labels[$name];
        }

        return $values;
    }
}
