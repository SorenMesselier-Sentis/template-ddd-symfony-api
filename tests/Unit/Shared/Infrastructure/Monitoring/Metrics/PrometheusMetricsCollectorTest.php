<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Monitoring\Metrics;

use App\Shared\Infrastructure\Monitoring\Metrics\PrometheusMetricsCollector;
use App\Tests\Unit\UnitTestCase;
use Prometheus\CollectorRegistry;
use Prometheus\MetricFamilySamples;
use Prometheus\Storage\InMemory;

final class PrometheusMetricsCollectorTest extends UnitTestCase
{
    private PrometheusMetricsCollector $collector;

    private CollectorRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = new CollectorRegistry(new InMemory(), false);
        $this->collector = new PrometheusMetricsCollector($this->registry);
    }

    public function testItIncrementsCounter(): void
    {
        $this->collector->incrementCounter('requests_total', ['method' => 'GET'], 2.0);

        $requestSamples = $this->samplesNamed($this->registry->getMetricFamilySamples(), 'requests_total');

        $this->assertCount(1, $requestSamples);
        $this->assertSame(2.0, $requestSamples[0]['value']);
        $this->assertSame(['GET'], $requestSamples[0]['labelValues']);
    }

    public function testItObservesHistogram(): void
    {
        $this->collector->observeHistogram('request_duration_seconds', 0.25, [
            'method' => 'POST',
            'status_code' => '200',
        ]);

        $sumSamples = $this->samplesNamed($this->registry->getMetricFamilySamples(), 'request_duration_seconds_sum');

        $this->assertNotEmpty($sumSamples);
        $this->assertSame(0.25, $sumSamples[0]['value']);
    }

    public function testItSetsGauge(): void
    {
        $this->collector->setGauge('workers_active', 3.0, ['pool' => 'default']);

        $gaugeSamples = $this->samplesNamed($this->registry->getMetricFamilySamples(), 'workers_active');

        $this->assertCount(1, $gaugeSamples);
        $this->assertSame(3.0, $gaugeSamples[0]['value']);
        $this->assertSame(['default'], $gaugeSamples[0]['labelValues']);
    }

    /**
     * @param MetricFamilySamples[] $families
     *
     * @return list<array{value: float, labelValues: list<string>}>
     */
    private function samplesNamed(array $families, string $name): array
    {
        $samples = [];

        foreach ($families as $family) {
            foreach ($family->getSamples() as $sample) {
                if ($sample->getName() !== $name) {
                    continue;
                }

                $samples[] = [
                    'value' => (float) $sample->getValue(),
                    'labelValues' => array_map('strval', $sample->getLabelValues()),
                ];
            }
        }

        return $samples;
    }
}
