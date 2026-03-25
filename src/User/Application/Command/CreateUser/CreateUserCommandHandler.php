<?php

declare(strict_types=1);

namespace App\User\Application\Command\CreateUser;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Domain\ValueObject\Email;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\HashedPassword;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\UserName;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use UserAlreadyExistsException;

#[AsMessageHandler(bus: 'command.bus')]
final class CreateUserCommandHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly EventBusInterface $eventBus,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(CreateUserCommand $command): void
    {
        $this->logger->info('Creating user', ['email' => $command->email]);

        if ($this->repository->existsByEmail(Email::fromString($command->email))) {
            throw UserAlreadyExistsException::withEmail($command->email);
        }

        $user = User::create(
            id: UserId::fromString($command->id),
            firstName: UserName::fromString($command->firstName),
            lastName: UserName::fromString($command->lastName),
            email: Email::fromString($command->email),
            password: HashedPassword::fromPlainPassword($command->password),
        );

        $this->repository->save($user);
        $this->eventBus->publish(...$user->pullDomainEvents());

        $this->logger->info('User created', ['id' => $command->id]);
    }
}
