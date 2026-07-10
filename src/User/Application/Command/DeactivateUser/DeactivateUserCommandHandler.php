<?php

declare(strict_types=1);

namespace App\User\Application\Command\DeactivateUser;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Logging\LoggerInterface;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\RefreshTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\UserStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class DeactivateUserCommandHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
        private readonly EventBusInterface $eventBus,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(DeactivateUserCommand $command): void
    {
        $this->logger->info('Deactivating user', ['id' => $command->id]);

        $user = $this->repository->findById(UserId::fromString($command->id));

        if (null === $user || UserStatus::DELETED === $user->status()) {
            throw UserNotFoundException::withId($command->id);
        }

        if (UserStatus::INACTIVE === $user->status()) {
            return;
        }

        $user->deactivate();

        $this->repository->save($user);
        $this->refreshTokenRepository->revokeAllForUser($user->id()->value());
        $this->eventBus->publish(...$user->pullDomainEvents());

        $this->logger->info('User deactivated', ['id' => $command->id]);
    }
}
