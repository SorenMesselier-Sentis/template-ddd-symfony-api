<?php

declare(strict_types=1);

namespace App\User\Application\Command\ResendVerificationEmail;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Logging\LoggerInterface;
use App\User\Domain\Entity\EmailVerificationToken as EmailVerificationTokenEntity;
use App\User\Domain\Event\EmailVerificationRequested;
use App\User\Domain\Exception\EmailAlreadyVerifiedException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\EmailVerificationTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\Security\UserContextInterface;
use App\User\Domain\ValueObject\EmailVerificationTokenId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class ResendVerificationEmailCommandHandler
{
    private const TOKEN_TTL_SECONDS = 86400;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly EmailVerificationTokenRepositoryInterface $tokenRepository,
        private readonly UserContextInterface $userContext,
        private readonly EventBusInterface $eventBus,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(ResendVerificationEmailCommand $command): void
    {
        $userId = $this->userContext->userId();
        $this->logger->info('Resend verification email', ['id' => $userId->value()]);

        $user = $this->userRepository->findById($userId);

        if (null === $user) {
            throw UserNotFoundException::withId($userId->value());
        }

        if ($user->isEmailVerified()) {
            throw EmailAlreadyVerifiedException::create();
        }

        $this->tokenRepository->revokeAllForUser($user->id()->value());

        $token = bin2hex(random_bytes(32));
        $tokenEntity = EmailVerificationTokenEntity::create(
            id: EmailVerificationTokenId::random(),
            userId: $user->id(),
            token: $token,
            expiresAt: new \DateTimeImmutable(sprintf('+%d seconds', self::TOKEN_TTL_SECONDS)),
        );

        $this->tokenRepository->save($tokenEntity);

        $this->eventBus->publish(new EmailVerificationRequested(
            aggregateId: $user->id()->value(),
            email: $user->email()->value(),
            firstName: $user->firstName()->value(),
            token: $token,
        ));
    }
}
