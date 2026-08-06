<?php

declare(strict_types=1);

namespace App\Project\Application\Query\GetTasks;

use App\Project\Domain\Exception\ProjectNotFoundException;
use App\Project\Domain\Repository\ProjectRepositoryInterface;
use App\Project\Domain\Repository\TaskRepositoryInterface;
use App\Project\Domain\Security\OwnerContextInterface;
use App\Project\Domain\ValueObject\ProjectId;
use App\Shared\Domain\Exception\ForbiddenException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetTasksQueryHandler
{
    private const ADMIN_ROLE = 'ROLE_ADMIN';

    public function __construct(
        private readonly TaskRepositoryInterface $repository,
        private readonly ProjectRepositoryInterface $projectRepository,
        private readonly OwnerContextInterface $ownerContext,
    ) {
    }

    public function __invoke(GetTasksQuery $query): TasksResponse
    {
        $projectId = ProjectId::fromString($query->projectId);
        $project = $this->projectRepository->findById($projectId);

        if (null === $project) {
            throw ProjectNotFoundException::withId($query->projectId);
        }

        if (!$this->isAdmin() && !$project->ownerId()->equals($this->ownerContext->ownerId())) {
            throw ForbiddenException::create();
        }

        $entities = $this->repository->findByProjectIdAndFilters($projectId, $query->filters);
        $total = $this->repository->countByProjectIdAndFilters($projectId, $query->filters);

        return new TasksResponse(
            items: array_map(static fn ($e) => new TaskItemResponse($e), $entities),
            total: $total,
            page: $query->filters->pagination->page,
            limit: $query->filters->pagination->limit,
        );
    }

    private function isAdmin(): bool
    {
        return in_array(self::ADMIN_ROLE, $this->ownerContext->roles(), true);
    }
}
