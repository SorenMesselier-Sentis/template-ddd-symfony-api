<?php

declare(strict_types=1);

namespace App\User\Application\Command\UpdateUser;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Domain\ValueObject\Email;
use App\User\Application\Security\UserOwnershipGuard;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\UserName;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class UpdateUserCommandHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly EventBusInterface $eventbus,
        private readonly LoggerInterface $logger,
        private readonly UserOwnershipGuard $ownershipGuard,
    ) {
    }

    public function __invoke(UpdateUserCommand $command): void
    {
        $this->logger->info('Updating user', ['id' => $command->id]);

        $this->ownershipGuard->assertCanAccessUser($command->id);

        $user = $this->repository->findById(UserId::fromString($command->id));

        if (null === $user) {
            throw UserNotFoundException::withId($command->id);
        }

        if (null !== $command->firstName || null !== $command->lastName) {
            $user->updateName(
                firstName: UserName::fromString($command->firstName ?? $user->firstName()->value()),
                lastName: UserName::fromString($command->lastName ?? $user->lastName()->value()),
            );
        }

        if (null !== $command->email) {
            $email = Email::fromString($command->email);

            if ($this->repository->existsByEmail($email) && $command->email !== $user->email()->value()) {
                throw UserAlreadyExistsException::withEmail($command->email);
            }

            $user->updateEmail($email);
        }

        $this->repository->save($user);
        $this->eventbus->publish(...$user->pullDomainEvents());

        $this->logger->info('User updated', ['id' => $command->id]);
    }
}
