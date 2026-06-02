<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Monitoring\Metrics;

use App\Shared\Domain\Monitoring\MetricsCollectorInterface;
use App\Shared\Infrastructure\Monitoring\Metrics\BuildInfoMetricsInitializer;
use App\Tests\Unit\UnitTestCase;

final class BuildInfoMetricsInitializerTest extends UnitTestCase
{
    public function testItSetsBuildInfoGaugeOnlyOnce(): void
    {
        $metrics = $this->createMock(MetricsCollectorInterface::class);
        $metrics
            ->expects($this->once())
            ->method('setGauge')
            ->with('app_build_info', 1.0, [
                'version' => '1.2.3',
                'php_version' => PHP_VERSION,
                'env' => 'test',
            ]);

        $initializer = new BuildInfoMetricsInitializer($metrics, '1.2.3', 'test');

        $initializer->initialize();
        $initializer->initialize();
    }
}
