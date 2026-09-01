<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Security;

use App\Shared\Domain\Exception\InsufficientPrivilegesException;
use App\Shared\Domain\Exception\UnauthenticatedException;
use App\Shared\Domain\Security\RoleRequirement;
use App\Shared\Infrastructure\Security\PrincipalRoleAuthorizer;
use App\Tests\Unit\UnitTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class PrincipalRoleAuthorizerTest extends UnitTestCase
{
    public function testItThrowsWhenNoPrincipalIsAuthenticated(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $this->expectException(UnauthenticatedException::class);

        (new PrincipalRoleAuthorizer($security))->assert(RoleRequirement::authenticated());
    }

    public function testAuthenticatedRequirementIsSatisfiedByAnyPrincipal(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(new InMemoryUser('client', null, ['ROLE_API_CLIENT']));

        (new PrincipalRoleAuthorizer($security))->assert(RoleRequirement::authenticated());
        $this->addToAssertionCount(1);
    }

    public function testItThrowsWhenRoleIsMissing(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(new InMemoryUser('client', null, ['ROLE_API_CLIENT']));

        $this->expectException(InsufficientPrivilegesException::class);

        (new PrincipalRoleAuthorizer($security))->assert(RoleRequirement::admin());
    }

    public function testItAllowsAMachineClientCarryingTheRequiredScopeRole(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(new InMemoryUser('client', null, ['ROLE_API_CLIENT', 'SCOPE_DOCUMENTS_WRITE']));

        (new PrincipalRoleAuthorizer($security))->assert(RoleRequirement::any('ROLE_ADMIN', 'SCOPE_DOCUMENTS_WRITE'));
        $this->addToAssertionCount(1);
    }
}
