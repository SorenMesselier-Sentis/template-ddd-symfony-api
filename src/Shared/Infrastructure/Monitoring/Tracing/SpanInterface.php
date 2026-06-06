<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Monitoring\Tracing;

interface SpanInterface
{
    public function end(): void;

    public function recordException(\Throwable $exception): void;

    public function setAttribute(string $key, mixed $value): void;
}
