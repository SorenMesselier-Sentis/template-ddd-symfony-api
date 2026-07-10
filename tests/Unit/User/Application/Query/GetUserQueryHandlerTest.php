<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Query;

use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\User\Domain\Mother\UserMother;
use App\User\Application\Query\GetUser\GetUserQuery;
use App\User\Application\Query\GetUser\GetUserQueryHandler;
use App\User\Application\Security\UserOwnershipGuard;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\Security\UserContextInterface;
use App\User\Domain\ValueObject\UserRole;
use PHPUnit\Framework\MockObject\MockObject;

final class GetUserQueryHandlerTest extends UnitTestCase
{
    /** @var UserRepositoryInterface&MockObject */
    private UserRepositoryInterface $repository;

    private GetUserQueryHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $userContext = $this->createStub(UserContextInterface::class);
        $userContext->method('roles')->willReturn([UserRole::ADMIN]);
        $ownershipGuard = new UserOwnershipGuard($userContext);
        $this->handler = new GetUserQueryHandler($this->repository, $ownershipGuard);
    }

    public function testItReturnsAUser(): void
    {
        $user = UserMother::create();
        $query = new GetUserQuery(id: $user->id()->value());

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($user);

        $response = ($this->handler)($query);

        $this->assertEquals($user->id()->value(), $response->id);
        $this->assertEquals($user->firstName()->value(), $response->firstName);
        $this->assertEquals($user->lastName()->value(), $response->lastName);
        $this->assertEquals($user->email()->value(), $response->email);
    }

    public function testItThrowsWhenUserNotFound(): void
    {
        $this->expectException(UserNotFoundException::class);

        $query = new GetUserQuery(id: UserMother::create()->id()->value());

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        ($this->handler)($query);
    }
}
