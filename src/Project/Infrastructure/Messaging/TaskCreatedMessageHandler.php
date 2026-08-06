<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Messaging;

use App\Project\Domain\Event\TaskCreated;
use App\Shared\Domain\Logging\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class TaskCreatedMessageHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(TaskCreated $event): void
    {
        $this->logger->info('Task created event received', [
            'aggregateId' => $event->aggregateId(),
        ]);

        // TODO: implement side effects
    }
}
