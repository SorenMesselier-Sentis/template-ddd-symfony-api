<?php

declare(strict_types=1);

namespace App\User\Application\Privacy;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Logging\LoggerInterface;
use App\Shared\Domain\Privacy\PersonalDataEraserInterface;
use App\Shared\Domain\ValueObject\Email;
use App\User\Domain\Repository\EmailVerificationTokenRepositoryInterface;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\User\Domain\Repository\RefreshTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\HashedPassword;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\UserName;
use App\User\Domain\ValueObject\UserStatus;

final class UserPersonalDataEraser implements PersonalDataEraserInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
        private readonly PasswordResetTokenRepositoryInterface $passwordResetTokenRepository,
        private readonly EmailVerificationTokenRepositoryInterface $emailVerificationTokenRepository,
        private readonly EventBusInterface $eventBus,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function key(): string
    {
        return 'profile';
    }

    public function erase(string $subjectId): void
    {
        $user = $this->repository->findByIdIncludingDeleted(UserId::fromString($subjectId));

        if (null === $user) {
            return;
        }

        if (UserStatus::DELETED !== $user->status()) {
            $user->delete();
        }

        $user->anonymize(
            firstName: UserName::fromString('deleted'),
            lastName: UserName::fromString('user'),
            email: Email::fromString(sprintf('deleted-%s@erased.invalid', bin2hex(random_bytes(8)))),
            password: HashedPassword::fromPlainPassword(bin2hex(random_bytes(16))),
        );

        $this->repository->save($user);
        $this->eventBus->publish(...$user->pullDomainEvents());

        $this->refreshTokenRepository->revokeAllForUser($subjectId);
        $this->passwordResetTokenRepository->revokeAllForUser($subjectId);
        $this->emailVerificationTokenRepository->revokeAllForUser($subjectId);

        $this->logger->info('Erased personal data for user', ['id' => $subjectId]);
    }
}
