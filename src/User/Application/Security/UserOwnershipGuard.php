<?php

declare(strict_types=1);

namespace App\User\Application\Security;

use App\Shared\Domain\Exception\ForbiddenException;
use App\User\Domain\Security\UserContextInterface;
use App\User\Domain\ValueObject\UserRole;

final class UserOwnershipGuard
{
    public function __construct(
        private readonly UserContextInterface $userContext,
    ) {
    }

    public function assertCanAccessUser(string $targetUserId): void
    {
        if ($this->isAdmin()) {
            return;
        }

        if ($this->userContext->userId()->value() !== $targetUserId) {
            throw ForbiddenException::create();
        }
    }

    public function isAdmin(): bool
    {
        foreach ($this->userContext->roles() as $role) {
            if (UserRole::ADMIN === $role) {
                return true;
            }
        }

        return false;
    }
}
