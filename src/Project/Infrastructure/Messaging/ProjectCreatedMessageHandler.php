<?php

declare(strict_types=1);

namespace App\Project\Infrastructure\Messaging;

use App\Project\Domain\Event\ProjectCreated;
use App\Shared\Domain\Logging\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class ProjectCreatedMessageHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(ProjectCreated $event): void
    {
        $this->logger->info('Project created event received', [
            'aggregateId' => $event->aggregateId(),
        ]);

        // TODO: implement side effects
    }
}
