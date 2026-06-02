<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Http\Controller;

use App\Shared\Infrastructure\Http\Controller\MetricsController;
use App\Shared\Infrastructure\Monitoring\Metrics\BuildInfoMetricsInitializer;
use App\Tests\Unit\UnitTestCase;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\InMemory;

final class MetricsControllerTest extends UnitTestCase
{
    public function testItReturns200WithRenderedMetrics(): void
    {
        $registry = new CollectorRegistry(new InMemory());
        $metrics = new class implements \App\Shared\Domain\Monitoring\MetricsCollectorInterface {
            public int $setGaugeCalls = 0;

            public function incrementCounter(string $name, array $labels = [], float $value = 1.0): void
            {
            }

            public function observeHistogram(string $name, float $value, array $labels = [], ?array $buckets = null): void
            {
            }

            public function setGauge(string $name, float $value, array $labels = []): void
            {
                if ('app_build_info' === $name) {
                    ++$this->setGaugeCalls;
                }
            }
        };
        $initializer = new BuildInfoMetricsInitializer($metrics, '1.0.0', 'test');
        $controller = new MetricsController($registry, $initializer);

        $response = $controller();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            RenderTextFormat::MIME_TYPE.'; charset=utf-8',
            $response->headers->get('Content-Type'),
        );
        $body = (string) $response->getContent();

        $this->assertStringContainsString('# HELP php_info ', $body);
        $this->assertStringContainsString('# TYPE php_info ', $body);
        $this->assertSame(1, $metrics->setGaugeCalls);
    }
}
