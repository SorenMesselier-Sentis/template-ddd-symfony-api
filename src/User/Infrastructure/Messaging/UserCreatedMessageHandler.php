<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Messaging;

use App\User\Domain\Event\UserCreated;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class UserCreatedMessageHandler
{
    public function __invoke(UserCreated $event): void
    {
    }
}
