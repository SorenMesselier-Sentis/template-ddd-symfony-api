<?php

declare(strict_types=1);

namespace App\Shared\Domain\Monitoring;

interface MetricsCollectorInterface
{
    /**
     * @param array<string, string|int|float|bool> $labels
     */
    public function incrementCounter(string $name, array $labels = [], float $value = 1.0): void;

    /**
     * @param array<string, string|int|float|bool> $labels
     * @param list<float>|null                     $buckets prometheus histogram buckets; null uses library defaults when registering
     */
    public function observeHistogram(string $name, float $value, array $labels = [], ?array $buckets = null): void;

    /**
     * @param array<string, string|int|float|bool> $labels
     */
    public function setGauge(string $name, float $value, array $labels = []): void;
}
