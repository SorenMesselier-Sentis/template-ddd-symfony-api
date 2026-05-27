<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Monitoring\Metrics;

use App\Shared\Infrastructure\Monitoring\Metrics\PrometheusCollectorRegistryFactory;
use App\Tests\Unit\UnitTestCase;
use Prometheus\CollectorRegistry;
use Prometheus\Exception\StorageException;

final class PrometheusCollectorRegistryFactoryTest extends UnitTestCase
{
    public function testItCreatesRegistryWithApcuStorage(): void
    {
        if (!\extension_loaded('apcu')) {
            $this->markTestSkipped('APCu extension is required to exercise the apcu storage adapter.');
        }

        $registry = (new PrometheusCollectorRegistryFactory('apcu'))->create();

        $this->assertInstanceOf(CollectorRegistry::class, $registry);
    }

    public function testItRejectsApcuStorageWhenExtensionIsMissing(): void
    {
        if (\extension_loaded('apcu')) {
            $this->markTestSkipped('APCu is loaded; cannot assert the missing-extension failure path.');
        }

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('APCu extension is not loaded');

        (new PrometheusCollectorRegistryFactory('apcu'))->create();
    }

    public function testItThrowsOnUnsupportedStorage(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unsupported METRICS_STORAGE value "memory"');

        (new PrometheusCollectorRegistryFactory('memory'))->create();
    }
}
