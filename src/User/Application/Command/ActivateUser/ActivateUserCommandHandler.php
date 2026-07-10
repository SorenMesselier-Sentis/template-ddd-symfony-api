<?php

declare(strict_types=1);

namespace App\User\Application\Command\ActivateUser;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Logging\LoggerInterface;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\UserStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class ActivateUserCommandHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly EventBusInterface $eventBus,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(ActivateUserCommand $command): void
    {
        $this->logger->info('Activating user', ['id' => $command->id]);

        $user = $this->repository->findById(UserId::fromString($command->id));

        if (null === $user || UserStatus::DELETED === $user->status()) {
            throw UserNotFoundException::withId($command->id);
        }

        if (UserStatus::ACTIVE === $user->status()) {
            return;
        }

        $user->activate();

        $this->repository->save($user);
        $this->eventBus->publish(...$user->pullDomainEvents());

        $this->logger->info('User activated', ['id' => $command->id]);
    }
}
