<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Monitoring\Tracing;

interface TracerInterface
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function startSpan(string $name, array $attributes = []): SpanInterface;
}
