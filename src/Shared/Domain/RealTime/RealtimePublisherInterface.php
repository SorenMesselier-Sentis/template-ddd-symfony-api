<?php

declare(strict_types=1);

namespace App\Shared\Domain\RealTime;

interface RealtimePublisherInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function publish(string $topic, array $data, bool $private = true): void;
}
