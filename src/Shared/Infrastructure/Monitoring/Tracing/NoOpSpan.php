<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Monitoring\Tracing;

final class NoOpSpan implements SpanInterface
{
    public function end(): void
    {
    }

    public function recordException(\Throwable $exception): void
    {
    }

    public function setAttribute(string $key, mixed $value): void
    {
    }
}
