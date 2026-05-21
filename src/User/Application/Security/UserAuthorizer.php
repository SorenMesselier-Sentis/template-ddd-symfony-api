<?php

declare(strict_types=1);

namespace App\User\Application\Security;

use App\User\Domain\Exception\InsufficientPrivilegesException;
use App\User\Domain\Security\UserContextInterface;
use App\User\Domain\ValueObject\UserRole;

final class UserAuthorizer
{
    public function __construct(
        private readonly UserContextInterface $userContext,
    ) {
    }

    public function assert(RoleRequirement $requirement): void
    {
        if (!$this->isSatisfied($requirement)) {
            throw InsufficientPrivilegesException::create();
        }
    }

    public function isSatisfied(RoleRequirement $requirement): bool
    {
        $userRoles = $this->userContext->roles();

        if ([] === $requirement->roles) {
            return $this->userContext->isAuthenticated();
        }

        return match ($requirement->mode) {
            RoleMatchMode::Any => $this->hasAnyRole($userRoles, $requirement->roles),
            RoleMatchMode::All => $this->hasAllRoles($userRoles, $requirement->roles),
        };
    }

    /**
     * @param list<UserRole> $userRoles
     * @param list<UserRole> $requiredRoles
     */
    private function hasAnyRole(array $userRoles, array $requiredRoles): bool
    {
        foreach ($requiredRoles as $requiredRole) {
            if (in_array($requiredRole, $userRoles, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<UserRole> $userRoles
     * @param list<UserRole> $requiredRoles
     */
    private function hasAllRoles(array $userRoles, array $requiredRoles): bool
    {
        foreach ($requiredRoles as $requiredRole) {
            if (!in_array($requiredRole, $userRoles, true)) {
                return false;
            }
        }

        return true;
    }
}
