<?php

declare(strict_types=1);

namespace App\User\Application\Command\UpdateUser;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Domain\ValueObject\Email;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\HashedPassword;
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
    ) {}

    public function __invoke(UpdateUserCommand $command): void
    {
        $this->logger->info('Updating user', ['id' => $command->id]);

        $user = $this->repository->findById(UserId::fromString($command->id));

        if ($user === null) {
            throw UserNotFoundException::withId($command->id);
        }

        if ($command->firstName !== null || $command->lastName !== null) {
            $user->updateName(
                firstName: UserName::fromString($command->firstName ?? $user->firstName()->value()),
                lastName: UserName::fromString($command->lastName ?? $user->lastName()->value()),
            );
        }

        if ($command->email !== null) {
            $email = Email::fromString($command->email);

            if ($this->repository->existsByEmail($email) && $command->email !== $user->email()->value()) {
                throw UserAlreadyExistsException::withEmail($command->email);
            }

            $user->updateEmail($email);
        }

        if ($command->password !== null) {
            $user->updatePassword(HashedPassword::fromPlainPassword($command->password));
        }

        this->repository->save($user);
        $this->eventbus->publish(...$user->pullDomainEvents());

        $this->logger->info('User updated', ['id' => $command->id]);
    }
}
