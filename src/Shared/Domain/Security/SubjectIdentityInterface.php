<?php

declare(strict_types=1);

namespace App\Shared\Domain\Security;

/**
 * Implemented by a Symfony Security principal (UserInterface) that has a stable UUID identity of
 * its own — a human login's JWT `sub` claim, in practice. Not every principal has one: an OAuth2
 * client_credentials caller (see ApiClient\Infrastructure\Security\ApiClientSecurityAdapter)
 * doesn't "own" anything, so it deliberately does not implement this. Callers that need an owner
 * id (e.g. Document/Project's OwnerContextInterface) must check `instanceof` and fail closed
 * otherwise, the same way RoleRequirement already fails closed on unmatched roles.
 */
interface SubjectIdentityInterface
{
    public function subjectId(): string;
}
