<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Monitoring\Tracing;

use OpenTelemetry\API\Trace\SpanInterface as OtelSpanInterface;

final class OtlpSpan implements SpanInterface
{
    public function __construct(
        private readonly OtelSpanInterface $span,
    ) {
    }

    public function end(): void
    {
        $this->span->end();
    }

    public function recordException(\Throwable $exception): void
    {
        $this->span->recordException($exception);
    }

    public function setAttribute(string $key, mixed $value): void
    {
        if ('' === $key) {
            return;
        }

        if (\is_array($value) || \is_bool($value) || \is_float($value) || \is_int($value) || \is_string($value) || null === $value) {
            $this->span->setAttribute($key, $value);
        }
    }
}
