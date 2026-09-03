<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use App\Shared\Domain\Security\SubjectIdentityInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\ValueObject\UserRole;
use App\User\Domain\ValueObject\UserStatus;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class SecurityUserAdapter implements UserInterface, PasswordAuthenticatedUserInterface, SubjectIdentityInterface
{
    public function __construct(
        private readonly User $user,
    ) {
    }

    public function getUserIdentifier(): string
    {
        return $this->user->email()->value();
    }

    public function getPassword(): string
    {
        return $this->user->password()->value();
    }

    public function getRoles(): array
    {
        return array_map(
            fn (UserRole $role) => $role->value,
            $this->user->roles(),
        );
    }

    public function eraseCredentials(): void
    {
    }

    public function isActive(): bool
    {
        return UserStatus::ACTIVE === $this->user->status();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function subjectId(): string
    {
        return $this->user->id()->value();
    }
}
