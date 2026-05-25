<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Scheduler\Handler;

use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Infrastructure\Messaging\Outbox\OutboxRelay;
use App\Shared\Infrastructure\Scheduler\Message\RelayOutboxMessages;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class RelayOutboxMessagesHandler
{
    public function __construct(
        private readonly OutboxRelay $outboxRelay,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(RelayOutboxMessages $message): void
    {
        try {
            $this->outboxRelay->relay();
        } catch (\Throwable $e) {
            $this->logger->error('Scheduled outbox relay failed', [
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
