<?php

declare(strict_types=1);

namespace App\User\Application\Command\ResetPassword;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Logging\LoggerInterface;
use App\User\Domain\Exception\InvalidPasswordResetTokenException;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\User\Domain\Repository\RefreshTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\HashedPassword;
use App\User\Domain\ValueObject\UserStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class ResetPasswordCommandHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordResetTokenRepositoryInterface $tokenRepository,
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
        private readonly EventBusInterface $eventBus,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(ResetPasswordCommand $command): void
    {
        $this->logger->info('Password reset attempt');

        $tokenEntity = $this->tokenRepository->findByToken($command->token);

        if (null === $tokenEntity || $tokenEntity->isRevoked() || $tokenEntity->isExpired()) {
            throw InvalidPasswordResetTokenException::create();
        }

        $user = $this->userRepository->findById($tokenEntity->userId());

        if (null === $user || UserStatus::DELETED === $user->status()) {
            throw InvalidPasswordResetTokenException::create();
        }

        $user->updatePassword(HashedPassword::fromPlainPassword($command->password));
        $tokenEntity->revoke();

        $this->userRepository->save($user);
        $this->tokenRepository->save($tokenEntity);
        $this->refreshTokenRepository->revokeAllForUser($user->id()->value());
        $this->eventBus->publish(...$user->pullDomainEvents());

        $this->logger->info('Password reset completed', ['id' => $user->id()->value()]);
    }
}
