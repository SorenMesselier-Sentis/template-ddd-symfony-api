<?php

declare(strict_types=1);

namespace App\User\Application\Command\RequestPasswordReset;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Logging\LoggerInterface;
use App\User\Domain\Entity\PasswordResetToken as PasswordResetTokenEntity;
use App\User\Domain\Event\PasswordResetRequested;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\PasswordResetTokenId;
use App\User\Domain\ValueObject\UserStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class RequestPasswordResetCommandHandler
{
    private const TOKEN_TTL_SECONDS = 3600;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordResetTokenRepositoryInterface $tokenRepository,
        private readonly EventBusInterface $eventBus,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(RequestPasswordResetCommand $command): void
    {
        $this->logger->info('Password reset requested', ['email' => $command->email->value()]);

        $user = $this->userRepository->findByEmail($command->email);

        if (null === $user || UserStatus::DELETED === $user->status()) {
            return;
        }

        $this->tokenRepository->revokeAllForUser($user->id()->value());

        $token = bin2hex(random_bytes(32));
        $tokenEntity = PasswordResetTokenEntity::create(
            id: PasswordResetTokenId::random(),
            userId: $user->id(),
            token: $token,
            expiresAt: new \DateTimeImmutable(sprintf('+%d seconds', self::TOKEN_TTL_SECONDS)),
        );

        $this->tokenRepository->save($tokenEntity);

        $this->eventBus->publish(new PasswordResetRequested(
            aggregateId: $user->id()->value(),
            email: $user->email()->value(),
            firstName: $user->firstName()->value(),
            token: $token,
        ));
    }
}
