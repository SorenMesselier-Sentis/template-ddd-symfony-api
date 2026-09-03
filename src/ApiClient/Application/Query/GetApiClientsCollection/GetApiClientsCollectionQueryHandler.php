<?php

declare(strict_types=1);

namespace App\ApiClient\Application\Query\GetApiClientsCollection;

use App\ApiClient\Domain\Repository\ApiClientRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetApiClientsCollectionQueryHandler
{
    public function __construct(
        private readonly ApiClientRepositoryInterface $repository,
    ) {
    }

    public function __invoke(GetApiClientsCollectionQuery $query): ApiClientsResponse
    {
        $entities = $this->repository->findByFilters($query->filters);
        $total = $this->repository->countByFilters($query->filters);

        return new ApiClientsResponse(
            items: array_map(static fn ($e) => new ApiClientItemResponse($e), $entities),
            total: $total,
            page: $query->filters->pagination->page,
            limit: $query->filters->pagination->limit,
        );
    }
}
