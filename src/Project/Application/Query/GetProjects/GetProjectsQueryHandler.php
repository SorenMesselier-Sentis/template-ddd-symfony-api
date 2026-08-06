<?php

declare(strict_types=1);

namespace App\Project\Application\Query\GetProjects;

use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Project\Domain\Security\OwnerContextInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetProjectsQueryHandler
{
    public function __construct(
        private readonly ProjectRepositoryInterface $repository,
        private readonly OwnerContextInterface $ownerContext,
    ) {
    }

    public function __invoke(GetProjectsQuery $query): ProjectsResponse
    {
        $ownerId = $this->ownerContext->ownerId();
        $entities = $this->repository->findByOwnerIdAndFilters($ownerId, $query->filters);
        $total = $this->repository->countByOwnerIdAndFilters($ownerId, $query->filters);

        return new ProjectsResponse(
            items: array_map(static fn ($e) => new ProjectItemResponse($e), $entities),
            total: $total,
            page: $query->filters->pagination->page,
            limit: $query->filters->pagination->limit,
        );
    }
}
