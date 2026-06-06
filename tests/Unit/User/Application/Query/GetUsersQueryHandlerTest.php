<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Query;

use App\Shared\Domain\Filter\Filters;
use App\Shared\Domain\Filter\Order;
use App\Shared\Domain\Filter\Pagination;
use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\User\Domain\Mother\UserMother;
use App\User\Application\Query\GetUsers\GetUsersQuery;
use App\User\Application\Query\GetUsers\GetUsersQueryHandler;
use App\User\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;

final class GetUsersQueryHandlerTest extends UnitTestCase
{
    /** @var UserRepositoryInterface&MockObject */
    private UserRepositoryInterface $repository;

    private GetUsersQueryHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->handler = new GetUsersQueryHandler($this->repository);
    }

    public function testItReturnsPaginatedUsers(): void
    {
        $userA = UserMother::create();
        $userB = UserMother::create();
        $filters = new Filters([], Order::default(), new Pagination(page: 2, limit: 10));
        $query = new GetUsersQuery(filters: $filters);

        $this->repository
            ->expects($this->once())
            ->method('findByFilters')
            ->with($filters)
            ->willReturn([$userA, $userB]);

        $this->repository
            ->expects($this->once())
            ->method('countByFilters')
            ->with($filters)
            ->willReturn(42);

        $response = ($this->handler)($query);

        $this->assertCount(2, $response->users);
        $this->assertEquals(42, $response->total);
        $this->assertEquals(2, $response->page);
        $this->assertEquals(10, $response->limit);
        $this->assertEquals($userA->id()->value(), $response->users[0]->id);
        $this->assertEquals($userB->email()->value(), $response->users[1]->email);
    }
}
