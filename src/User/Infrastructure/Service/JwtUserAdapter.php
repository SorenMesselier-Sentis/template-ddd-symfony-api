<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Service;

use App\User\Domain\Entity\User;
use App\User\Domain\ValueObject\UserRole;
use Symfony\Component\Security\Core\User\UserInterface;

final class JwtUserAdapter implements UserInterface
{
    public function __construct(
        private readonly User $user,
    ) {
    }

    public function getUserIdentifier(): string
    {
        $identifier = $this->user->email()->value();
        if ('' === $identifier) {
            throw new \LogicException('User identifier cannot be empty.');
        }

        return $identifier;
    }

    public function getRoles(): array
    {
        return array_map(
            static fn (UserRole $role): string => $role->value,
            $this->user->roles(),
        );
    }

    public function eraseCredentials(): void
    {
    }
}
