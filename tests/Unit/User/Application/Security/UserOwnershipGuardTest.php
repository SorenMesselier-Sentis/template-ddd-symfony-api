<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Security;

use App\Shared\Domain\Exception\ForbiddenException;
use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\User\Domain\Mother\UserIdMother;
use App\User\Application\Security\UserOwnershipGuard;
use App\User\Domain\Security\UserContextInterface;
use App\User\Domain\ValueObject\UserRole;
use PHPUnit\Framework\MockObject\MockObject;

final class UserOwnershipGuardTest extends UnitTestCase
{
    /** @var UserContextInterface&MockObject */
    private UserContextInterface $userContext;

    private UserOwnershipGuard $guard;

    protected function setUp(): void
    {
        $this->userContext = $this->createMock(UserContextInterface::class);
        $this->guard = new UserOwnershipGuard($this->userContext);
    }

    public function testAdminCanAccessAnyUser(): void
    {
        $this->userContext->expects($this->atLeastOnce())->method('roles')->willReturn([UserRole::ADMIN, UserRole::USER]);

        $this->guard->assertCanAccessUser(UserIdMother::random()->value());

        $this->assertTrue($this->guard->isAdmin());
    }

    public function testUserCanAccessOwnProfile(): void
    {
        $userId = UserIdMother::random();
        $this->userContext->expects($this->atLeastOnce())->method('roles')->willReturn([UserRole::USER]);
        $this->userContext->expects($this->once())->method('userId')->willReturn($userId);

        $this->guard->assertCanAccessUser($userId->value());

        $this->assertFalse($this->guard->isAdmin());
    }

    public function testUserCannotAccessOtherProfile(): void
    {
        $this->expectException(ForbiddenException::class);

        $this->userContext->expects($this->atLeastOnce())->method('roles')->willReturn([UserRole::USER]);
        $this->userContext->expects($this->once())->method('userId')->willReturn(UserIdMother::random());

        $this->guard->assertCanAccessUser(UserIdMother::random()->value());
    }
}
