<?php

declare(strict_types=1);

namespace App\User\Application\Command\ReplaceUser;

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
final class ReplaceUserCommandHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly EventBusInterface $eventBus,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(ReplaceUserCommand $command): void
    {
        $this->logger->info('Replace user informations', ['id' => $command->id]);

        $user = $this->repository->findById(UserId::fromString($command->id));

        if ($user === null) {
            throw UserNotFoundException::withId($command->id);
        }

        $newEmail = Email::fromString($command->email);

        if (
            $this->repository->existsByEmail($newEmail)
            && $command->email !== $user->email()->value()
        ) {
            throw UserAlreadyExistsException::withEmail($command->email);
        }

        $user->replace(
            firstName: UserName::fromString($command->firstName),
            lastName: UserName::fromString($command->lastName),
            email: $newEmail,
            password: HashedPassword::fromPlainPassword($command->password),
        );

        $this->repository->save($user);
        $this->eventBus->publish(...$user->pullDomainEvents());
        $this->logger->info('User replaced', ['id' => $command->id]);
    }
}
