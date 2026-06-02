<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Monitoring\Tracing;

use OpenTelemetry\API\Trace\TracerInterface as OtelTracerInterface;

final class OtlpTracer implements TracerInterface
{
    public function __construct(
        private readonly OtelTracerInterface $tracer,
    ) {
    }

    public function startSpan(string $name, array $attributes = []): SpanInterface
    {
        $builder = $this->tracer->spanBuilder('' !== $name ? $name : 'span');

        foreach ($attributes as $key => $value) {
            if (!\is_string($key) || '' === $key) {
                continue;
            }

            if (\is_array($value) || \is_bool($value) || \is_float($value) || \is_int($value) || \is_string($value) || null === $value) {
                $builder->setAttribute($key, $value);
            }
        }

        return new OtlpSpan($builder->startSpan());
    }
}
