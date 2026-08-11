<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\RealTime;

use App\Shared\Domain\RealTime\RealtimePublisherInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

final class MercureRealtimePublisher implements RealtimePublisherInterface
{
    public function __construct(
        private readonly HubInterface $hub,
    ) {
    }

    public function publish(string $topic, array $data, bool $private = true): void
    {
        $this->hub->publish(new Update(
            topics: $topic,
            data: json_encode($data, \JSON_THROW_ON_ERROR),
            private: $private,
        ));
    }
}
