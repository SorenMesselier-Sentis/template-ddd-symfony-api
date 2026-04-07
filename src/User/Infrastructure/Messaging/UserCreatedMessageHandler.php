<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Messaging;

use App\User\Domain\Event\UserCreated;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use App\Shared\Domain\Bus\Event\EventBusInterface;

#[AsMessageHandler(bus: 'event.bus')]
final class UserCreatedMessageHandler
{
    public function __construct(
        private readonly EventBusInterface $eventBus
    ) {}

    public function __invoke(UserCreated $event): void
    {}
}
