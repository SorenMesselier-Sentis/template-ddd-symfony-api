<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Monitoring\Tracing;

use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;

final class TracerFactory
{
    public function __construct(
        private readonly ?string $otlpEndpoint,
    ) {
    }

    public function create(): TracerInterface
    {
        if (null === $this->otlpEndpoint || '' === $this->otlpEndpoint) {
            return new NoOpTracer();
        }

        $transport = (new OtlpHttpTransportFactory())->create(
            endpoint: $this->otlpEndpoint,
            contentType: 'application/json',
        );
        $exporter = new SpanExporter($transport);
        $provider = TracerProvider::builder()
            ->addSpanProcessor(new SimpleSpanProcessor($exporter))
            ->build();

        return new OtlpTracer($provider->getTracer('template-ddd-symfony'));
    }
}
