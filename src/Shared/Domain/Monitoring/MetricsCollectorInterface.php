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
     */
    public function observeHistogram(string $name, float $value, array $labels = []): void;

    /**
     * @param array<string, string|int|float|bool> $labels
     */
    public function setGauge(string $name, float $value, array $labels = []): void;
}
