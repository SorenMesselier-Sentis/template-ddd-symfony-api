<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use App\User\Domain\Exception\MissingTokenException;
use App\User\Domain\Security\UserContextInterface;
use App\User\Domain\ValueObject\UserId;
use Symfony\Bundle\SecurityBundle\Security;

final class HttpUserContext implements UserContextInterface
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function userId(): UserId
    {
        return $this->securityUser()->getUser()->id();
    }

    public function roles(): array
    {
        return $this->securityUser()->getUser()->roles();
    }

    public function isAuthenticated(): bool
    {
        return $this->security->getUser() instanceof SecurityUserAdapter;
    }

    private function securityUser(): SecurityUserAdapter
    {
        $user = $this->security->getUser();

        if (!$user instanceof SecurityUserAdapter) {
            throw MissingTokenException::create();
        }

        return $user;
    }
}
