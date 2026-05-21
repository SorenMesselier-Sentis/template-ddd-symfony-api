<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use App\Shared\Domain\ValueObject\Email;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserStatus;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException as SymfonyUserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<UserInterface>
 */
final class SecurityUserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->repository->findByEmail(
            Email::fromString($identifier)
        );

        if (null === $user) {
            $exception = new SymfonyUserNotFoundException(
                sprintf('User with email "%s" was not found.', $identifier)
            );
            $exception->setUserIdentifier($identifier);

            throw $exception;
        }

        if (UserStatus::ACTIVE !== $user->status()) {
            throw new DisabledException(sprintf('User "%s" is not active.', $identifier));
        }

        return new SecurityUserAdapter($user);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof SecurityUserAdapter) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', $user::class));
        }

        $refreshed = $this->repository->findByEmail(
            Email::fromString($user->getUserIdentifier())
        );

        if (null === $refreshed) {
            $exception = new SymfonyUserNotFoundException(
                sprintf('User "%s" could not be refreshed.', $user->getUserIdentifier())
            );
            $exception->setUserIdentifier($user->getUserIdentifier());

            throw $exception;
        }

        // AC6 — INACTIVE ou DELETED → DisabledException
        if (in_array($refreshed->status(), [UserStatus::INACTIVE, UserStatus::DELETED], true)) {
            throw new DisabledException(sprintf('User "%s" is no longer active.', $user->getUserIdentifier()));
        }

        return new SecurityUserAdapter($refreshed);
    }

    public function supportsClass(string $class): bool
    {
        return SecurityUserAdapter::class === $class;
    }
}
