<?php

declare(strict_types=1);

namespace App\User\Application\Command\ChangePassword;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Logging\LoggerInterface;
use App\User\Domain\Exception\InvalidCurrentPasswordException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\RefreshTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\Security\UserContextInterface;
use App\User\Domain\ValueObject\HashedPassword;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class ChangePasswordCommandHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
        private readonly UserContextInterface $userContext,
        private readonly EventBusInterface $eventBus,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(ChangePasswordCommand $command): void
    {
        $userId = $this->userContext->userId();
        $this->logger->info('Change password', ['id' => $userId->value()]);

        $user = $this->userRepository->findById($userId);

        if (null === $user) {
            throw UserNotFoundException::withId($userId->value());
        }

        if (!$user->password()->verify($command->currentPassword)) {
            throw InvalidCurrentPasswordException::create();
        }

        $user->updatePassword(HashedPassword::fromPlainPassword($command->newPassword));

        $this->userRepository->save($user);
        $this->refreshTokenRepository->revokeAllForUser($user->id()->value());
        $this->eventBus->publish(...$user->pullDomainEvents());

        $this->logger->info('Password changed', ['id' => $user->id()->value()]);
    }
}
