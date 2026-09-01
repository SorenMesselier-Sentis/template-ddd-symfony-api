<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security;

use App\Shared\Domain\Exception\InsufficientPrivilegesException;
use App\Shared\Domain\Exception\UnauthenticatedException;
use App\Shared\Domain\Security\AuthorizedMessageContract;
use App\Shared\Domain\Security\MessageAuthorizerInterface;
use App\Shared\Domain\Security\RoleMatchMode;
use App\Shared\Domain\Security\RoleRequirement;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Generic replacement for the former User-BC-only `UserAuthorizer`: reads roles off whatever
 * Symfony Security principal is currently authenticated (any UserInterface::getRoles()), so
 * `RoleRequirement` checks work uniformly for human JWT logins (SecurityUserAdapter) and
 * machine OAuth2 clients (ApiClientSecurityAdapter) alike — see ApiClient BC's
 * OAuth2ClientAuthenticator. Depends only on Shared/Domain + Symfony Security, never a
 * bounded-context type, so it can stay the single alias for MessageAuthorizerInterface.
 */
final class PrincipalRoleAuthorizer implements MessageAuthorizerInterface
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function authorize(AuthorizedMessageContract $message): void
    {
        $this->assert($message->roleRequirement());
    }

    public function assert(RoleRequirement $requirement): void
    {
        $user = $this->security->getUser();

        if (null === $user) {
            throw UnauthenticatedException::create();
        }

        if (!$this->isSatisfied($requirement)) {
            throw InsufficientPrivilegesException::create();
        }
    }

    public function isSatisfied(RoleRequirement $requirement): bool
    {
        $user = $this->security->getUser();

        if (null === $user) {
            return false;
        }

        if ([] === $requirement->roles) {
            return true;
        }

        $userRoles = $user->getRoles();

        return match ($requirement->mode) {
            RoleMatchMode::Any => [] !== array_intersect($requirement->roles, $userRoles),
            RoleMatchMode::All => [] === array_diff($requirement->roles, $userRoles),
        };
    }
}
