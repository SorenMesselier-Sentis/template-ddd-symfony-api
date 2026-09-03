<?php

declare(strict_types=1);

namespace App\Tests\Unit\Project\Infrastructure\Security;

use App\Project\Infrastructure\Security\HttpOwnerContext;
use App\Shared\Domain\Exception\UnauthenticatedException;
use App\Shared\Domain\Security\SubjectIdentityInterface;
use App\Tests\Unit\UnitTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserInterface;

final class HttpOwnerContextTest extends UnitTestCase
{
    private const OWNER_ID = '11111111-1111-4111-8111-111111111111';

    public function testOwnerIdReadsTheSubjectIdOfTheAuthenticatedPrincipal(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(self::subjectUser(self::OWNER_ID, ['ROLE_USER']));

        $ownerContext = new HttpOwnerContext($security);

        $this->assertSame(self::OWNER_ID, $ownerContext->ownerId()->value());
    }

    public function testOwnerIdThrowsWhenNoPrincipalIsAuthenticated(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $this->expectException(UnauthenticatedException::class);

        (new HttpOwnerContext($security))->ownerId();
    }

    public function testOwnerIdThrowsForAPrincipalWithoutASubjectIdentity(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(new InMemoryUser('client', null, ['ROLE_API_CLIENT']));

        $this->expectException(UnauthenticatedException::class);

        (new HttpOwnerContext($security))->ownerId();
    }

    public function testRolesReflectsTheAuthenticatedPrincipalsRoles(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(self::subjectUser(self::OWNER_ID, ['ROLE_USER', 'ROLE_ADMIN']));

        $this->assertSame(['ROLE_USER', 'ROLE_ADMIN'], (new HttpOwnerContext($security))->roles());
    }

    public function testRolesIsEmptyWhenUnauthenticated(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $this->assertSame([], (new HttpOwnerContext($security))->roles());
    }

    public function testIsAuthenticatedIsTrueOnlyForAPrincipalWithASubjectIdentity(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(self::subjectUser(self::OWNER_ID, []));

        $this->assertTrue((new HttpOwnerContext($security))->isAuthenticated());
    }

    public function testIsAuthenticatedIsFalseForAPrincipalWithoutASubjectIdentity(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(new InMemoryUser('client', null, ['ROLE_API_CLIENT']));

        $this->assertFalse((new HttpOwnerContext($security))->isAuthenticated());
    }

    private static function subjectUser(string $subjectId, array $roles): UserInterface&SubjectIdentityInterface
    {
        return new class($subjectId, $roles) implements UserInterface, SubjectIdentityInterface {
            public function __construct(
                private readonly string $subjectId,
                private readonly array $roles,
            ) {
            }

            public function getRoles(): array
            {
                return $this->roles;
            }

            public function eraseCredentials(): void
            {
            }

            public function getUserIdentifier(): string
            {
                return $this->subjectId;
            }

            public function subjectId(): string
            {
                return $this->subjectId;
            }
        };
    }
}
