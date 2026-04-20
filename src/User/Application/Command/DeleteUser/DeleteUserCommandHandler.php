<?php

declare(strict_types=1);

namespace App\User\Application\Command\DeleteUser;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Logging\LoggerInterface;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class DeleteUserCommandHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly EventBusInterface $bus,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(DeleteUserCommand $command): void
    {
        $this->logger->info('Deleting user', ['id' => $command->id]);

        $user =$this->repository->findById(UserId::fromString($command->id));

        if ($user === null) {
            throw UserNotFoundException::withId($command->id);
        }

        $this->repository->delete($user);
        $this->bus->publish(...$user->pullDomainEvents());

        $this->logger->info('User deleted', ['id' => $command->id]);
    }
}
