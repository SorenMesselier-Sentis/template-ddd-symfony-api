<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Monitoring\Tracing;

final class NoOpTracer implements TracerInterface
{
    public function startSpan(string $name, array $attributes = []): SpanInterface
    {
        return new NoOpSpan();
    }
}
