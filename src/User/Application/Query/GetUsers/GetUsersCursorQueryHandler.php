<?php

declare(strict_types=1);

namespace App\User\Application\Query\GetUsers;

use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetUsersCursorQueryHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
    ) {
    }

    public function __invoke(GetUsersCursorQuery $query): UsersCursorResponse
    {
        $page = $this->repository->findByFiltersCursor($query->filters, $query->cursorPagination);

        return new UsersCursorResponse(
            users: array_map(fn ($user) => new UserItemResponse($user), $page->items),
            limit: $query->cursorPagination->limit,
            hasMore: null !== $page->nextCursor,
            nextCursor: $page->nextCursor,
        );
    }
}
