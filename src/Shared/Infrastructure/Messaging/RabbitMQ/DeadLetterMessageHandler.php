<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messaging\RabbitMQ;

use App\Shared\Domain\Logging\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class DeadLetterMessageHandler
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    public function __invoke(mixed $message): void
    {
        $this->logger->error('Message moved to dead letter queue', [
            'message' => $message::class
        ]);
    }
}
