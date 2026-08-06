<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Query;

use App\Shared\Domain\Filter\CursorPage;
use App\Shared\Domain\Filter\CursorPagination;
use App\Shared\Domain\Filter\Filters;
use App\Shared\Domain\Filter\Order;
use App\Shared\Domain\Filter\Pagination;
use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\User\Domain\Mother\UserMother;
use App\User\Application\Query\GetUsers\GetUsersCursorQuery;
use App\User\Application\Query\GetUsers\GetUsersCursorQueryHandler;
use App\User\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;

final class GetUsersCursorQueryHandlerTest extends UnitTestCase
{
    /** @var UserRepositoryInterface&MockObject */
    private UserRepositoryInterface $repository;

    private GetUsersCursorQueryHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->handler = new GetUsersCursorQueryHandler($this->repository);
    }

    public function testItReturnsCursorPageWithNextCursorWhenMoreResultsExist(): void
    {
        $userA = UserMother::create();
        $filters = new Filters([], Order::default(), new Pagination());
        $cursorPagination = CursorPagination::fromRequest(null, 20);
        $query = new GetUsersCursorQuery($filters, $cursorPagination);

        $this->repository
            ->expects($this->once())
            ->method('findByFiltersCursor')
            ->with($filters, $cursorPagination)
            ->willReturn(new CursorPage([$userA], 'next-token'));

        $response = ($this->handler)($query);

        $this->assertCount(1, $response->users);
        $this->assertSame($userA->id()->value(), $response->users[0]->id);
        $this->assertSame(20, $response->limit);
        $this->assertTrue($response->hasMore);
        $this->assertSame('next-token', $response->nextCursor);
    }

    public function testItReturnsNoNextCursorWhenOnLastPage(): void
    {
        $filters = new Filters([], Order::default(), new Pagination());
        $cursorPagination = CursorPagination::fromRequest(null, 20);
        $query = new GetUsersCursorQuery($filters, $cursorPagination);

        $this->repository
            ->expects($this->once())
            ->method('findByFiltersCursor')
            ->willReturn(new CursorPage([], null));

        $response = ($this->handler)($query);

        $this->assertSame([], $response->users);
        $this->assertFalse($response->hasMore);
        $this->assertNull($response->nextCursor);
    }
}
