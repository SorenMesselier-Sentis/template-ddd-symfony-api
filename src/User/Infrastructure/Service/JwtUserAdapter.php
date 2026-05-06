<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Service;

use App\User\Domain\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;

final class JwtUserAdapter implements UserInterface
{
    public function __construct(
        private readonly User $user,
    ) {
    }

    /** @return non-empty-string */
    public function getUserIdentifier(): string
    {
        return $this->user->email()->value();
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
    }
}
