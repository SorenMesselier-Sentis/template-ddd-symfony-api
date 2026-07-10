<?php

declare(strict_types=1);

namespace App\User\Application\Command\VerifyEmail;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Logging\LoggerInterface;
use App\User\Domain\Exception\EmailAlreadyVerifiedException;
use App\User\Domain\Exception\InvalidEmailVerificationTokenException;
use App\User\Domain\Repository\EmailVerificationTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class VerifyEmailCommandHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly EmailVerificationTokenRepositoryInterface $tokenRepository,
        private readonly EventBusInterface $eventBus,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(VerifyEmailCommand $command): void
    {
        $this->logger->info('Email verification attempt');

        $tokenEntity = $this->tokenRepository->findByToken($command->token);

        if (null === $tokenEntity || $tokenEntity->isRevoked() || $tokenEntity->isExpired()) {
            throw InvalidEmailVerificationTokenException::create();
        }

        $user = $this->userRepository->findById($tokenEntity->userId());

        if (null === $user || UserStatus::DELETED === $user->status()) {
            throw InvalidEmailVerificationTokenException::create();
        }

        if ($user->isEmailVerified()) {
            throw EmailAlreadyVerifiedException::create();
        }

        $user->verifyEmail();
        $tokenEntity->revoke();

        $this->userRepository->save($user);
        $this->tokenRepository->save($tokenEntity);
        $this->eventBus->publish(...$user->pullDomainEvents());

        $this->logger->info('Email verified', ['id' => $user->id()->value()]);
    }
}
