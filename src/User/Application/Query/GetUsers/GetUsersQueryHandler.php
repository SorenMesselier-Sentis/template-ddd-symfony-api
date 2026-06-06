<?php

declare(strict_types=1);

namespace App\User\Application\Query\GetUsers;

use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetUsersQueryHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
    ) {
    }

    public function __invoke(GetUsersQuery $query): UsersResponse
    {
        $users = $this->repository->findByFilters($query->filters);
        $total = $this->repository->countByFilters($query->filters);

        return new UsersResponse(
            users: array_map(fn ($user) => new UserItemResponse($user), $users),
            total: $total,
            page: $query->filters->pagination->page,
            limit: $query->filters->pagination->limit,
        );
    }
}
