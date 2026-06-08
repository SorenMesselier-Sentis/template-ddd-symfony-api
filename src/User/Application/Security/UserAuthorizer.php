<?php

declare(strict_types=1);

namespace App\User\Application\Security;

use App\Shared\Domain\Exception\UnauthenticatedException;
use App\Shared\Domain\Security\AuthorizedMessageContract;
use App\Shared\Domain\Security\MessageAuthorizerInterface;
use App\Shared\Domain\Security\RoleMatchMode;
use App\Shared\Domain\Security\RoleRequirement;
use App\User\Domain\Exception\InsufficientPrivilegesException;
use App\User\Domain\Security\UserContextInterface;
use App\User\Domain\ValueObject\UserRole;

final class UserAuthorizer implements MessageAuthorizerInterface
{
    public function __construct(
        private readonly UserContextInterface $userContext,
    ) {
    }

    public function authorize(AuthorizedMessageContract $message): void
    {
        $this->assert($message->roleRequirement());
    }

    public function assert(RoleRequirement $requirement): void
    {
        if (!$this->userContext->isAuthenticated()) {
            throw UnauthenticatedException::create();
        }

        if (!$this->isSatisfied($requirement)) {
            throw InsufficientPrivilegesException::create();
        }
    }

    public function isSatisfied(RoleRequirement $requirement): bool
    {
        if ([] === $requirement->roles) {
            return $this->userContext->isAuthenticated();
        }

        $userRoles = array_map(
            static fn (UserRole $role): string => $role->value,
            $this->userContext->roles(),
        );

        return match ($requirement->mode) {
            RoleMatchMode::Any => $this->hasAnyRole($userRoles, $requirement->roles),
            RoleMatchMode::All => $this->hasAllRoles($userRoles, $requirement->roles),
        };
    }

    /**
     * @param list<string> $userRoles
     * @param list<string> $requiredRoles
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
     * @param list<string> $userRoles
     * @param list<string> $requiredRoles
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
