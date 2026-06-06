<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Security;

use App\Tests\Unit\UnitTestCase;
use App\Shared\Domain\Security\RoleRequirement;
use App\User\Application\Security\UserAuthorizer;
use App\User\Domain\Exception\InsufficientPrivilegesException;
use App\User\Domain\Security\UserContextInterface;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\UserRole;

final class UserAuthorizerTest extends UnitTestCase
{
    public function testAssertPassesForAdminRequirementWhenUserIsAdmin(): void
    {
        $authorizer = new UserAuthorizer($this->userContext([UserRole::ADMIN, UserRole::USER]));

        $authorizer->assert(RoleRequirement::admin());

        $this->addToAssertionCount(1);
    }

    public function testAssertThrowsForAdminRequirementWhenUserIsNotAdmin(): void
    {
        $this->expectException(InsufficientPrivilegesException::class);

        $authorizer = new UserAuthorizer($this->userContext([UserRole::USER]));

        $authorizer->assert(RoleRequirement::admin());
    }

    public function testAnyModePassesWhenUserHasOneOfRequiredRoles(): void
    {
        $authorizer = new UserAuthorizer($this->userContext([UserRole::USER]));

        $authorizer->assert(RoleRequirement::any('ROLE_ADMIN', 'ROLE_USER'));

        $this->addToAssertionCount(1);
    }

    public function testAnyModeThrowsWhenUserHasNoneOfRequiredRoles(): void
    {
        $this->expectException(InsufficientPrivilegesException::class);

        $authorizer = new UserAuthorizer($this->userContext([UserRole::USER]));

        $authorizer->assert(RoleRequirement::any('ROLE_ADMIN'));
    }

    public function testAllModePassesWhenUserHasEveryRequiredRole(): void
    {
        $authorizer = new UserAuthorizer($this->userContext([UserRole::ADMIN, UserRole::USER]));

        $authorizer->assert(RoleRequirement::all('ROLE_ADMIN', 'ROLE_USER'));

        $this->addToAssertionCount(1);
    }

    public function testAllModeThrowsWhenUserIsMissingARequiredRole(): void
    {
        $this->expectException(InsufficientPrivilegesException::class);

        $authorizer = new UserAuthorizer($this->userContext([UserRole::ADMIN]));

        $authorizer->assert(RoleRequirement::all('ROLE_ADMIN', 'ROLE_USER'));
    }

    public function testAuthenticatedPassesWhenUserIsAuthenticated(): void
    {
        $authorizer = new UserAuthorizer($this->userContext([UserRole::USER], isAuthenticated: true));

        $authorizer->assert(RoleRequirement::authenticated());

        $this->addToAssertionCount(1);
    }

    public function testAuthenticatedThrowsWhenUserIsNotAuthenticated(): void
    {
        $this->expectException(InsufficientPrivilegesException::class);

        $authorizer = new UserAuthorizer($this->userContext([], isAuthenticated: false));

        $authorizer->assert(RoleRequirement::authenticated());
    }

    /**
     * @param list<UserRole> $roles
     */
    private function userContext(array $roles, bool $isAuthenticated = true): UserContextInterface
    {
        $context = $this->createStub(UserContextInterface::class);
        $context->method('roles')->willReturn($roles);
        $context->method('isAuthenticated')->willReturn($isAuthenticated);
        $context->method('userId')->willReturn(UserId::random());

        return $context;
    }
}
