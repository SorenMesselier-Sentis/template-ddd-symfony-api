<?php

declare(strict_types=1);

namespace App\User\Application\Command\EraseMyData;

use App\Shared\Domain\Privacy\PersonalDataEraserInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class EraseMyDataCommandHandler
{
    /**
     * @param iterable<PersonalDataEraserInterface> $erasers
     */
    public function __construct(
        private readonly iterable $erasers,
    ) {
    }

    public function __invoke(EraseMyDataCommand $command): void
    {
        foreach ($this->erasers as $eraser) {
            $eraser->erase($command->id);
        }
    }
}
