<?php

declare(strict_types=1);

namespace App\User\Application\Command\RegisterUser;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Domain\ValueObject\Email;
use App\User\Domain\Entity\EmailVerificationToken as EmailVerificationTokenEntity;
use App\User\Domain\Entity\User;
use App\User\Domain\Event\EmailVerificationRequested;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Domain\Repository\EmailVerificationTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\EmailVerificationTokenId;
use App\User\Domain\ValueObject\HashedPassword;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\UserName;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class RegisterUserCommandHandler
{
    private const TOKEN_TTL_SECONDS = 86400;

    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly EmailVerificationTokenRepositoryInterface $tokenRepository,
        private readonly EventBusInterface $eventBus,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(RegisterUserCommand $command): void
    {
        $this->logger->info('User registration attempt', ['email' => $command->email]);

        if ($this->repository->existsByEmail(Email::fromString($command->email))) {
            throw UserAlreadyExistsException::withEmail($command->email);
        }

        $user = User::register(
            id: UserId::fromString($command->id),
            firstName: UserName::fromString($command->firstName),
            lastName: UserName::fromString($command->lastName),
            email: Email::fromString($command->email),
            password: HashedPassword::fromPlainPassword($command->password),
        );

        $this->repository->save($user);

        $token = bin2hex(random_bytes(32));
        $tokenEntity = EmailVerificationTokenEntity::create(
            id: EmailVerificationTokenId::random(),
            userId: $user->id(),
            token: $token,
            expiresAt: new \DateTimeImmutable(sprintf('+%d seconds', self::TOKEN_TTL_SECONDS)),
        );

        $this->tokenRepository->save($tokenEntity);

        $events = $user->pullDomainEvents();
        $events[] = new EmailVerificationRequested(
            aggregateId: $user->id()->value(),
            email: $user->email()->value(),
            firstName: $user->firstName()->value(),
            token: $token,
        );

        $this->eventBus->publish(...$events);

        $this->logger->info('User registered', ['id' => $user->id()->value()]);
    }
}
