<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Command;

use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class MessengerCommandBus implements CommandBusInterface
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
    ) {
    }

    public function dispatch(Command $command): mixed
    {
        $envelope = $this->commandBus->dispatch($command);
        $stamp = $envelope->last(HandledStamp::class);

        return $stamp?->getResult();
    }
}
