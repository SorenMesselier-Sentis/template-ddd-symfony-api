<?php

declare(strict_types=1);

namespace App\Project\Domain\Repository;

use App\Project\Domain\Entity\Task;
use App\Project\Domain\ValueObject\ProjectId;
use App\Project\Domain\ValueObject\TaskId;
use App\Project\Domain\ValueObject\TaskTitle;
use App\Shared\Domain\Filter\Filters;

interface TaskRepositoryInterface
{
    public function save(Task $entity): void;

    public function findById(TaskId $id): ?Task;

    public function findByIdIncludingDeleted(TaskId $id): ?Task;

    public function existsByProjectIdAndTitle(ProjectId $projectId, TaskTitle $title): bool;

    /**
     * Todo/in-progress tasks — see Task::isActive(). Backs the "cannot delete
     * a project with active tasks" rule (ProjectHasActiveTasksException).
     */
    public function countActiveByProjectId(ProjectId $projectId): int;

    /** @return list<Task> */
    public function findByProjectIdAndFilters(ProjectId $projectId, Filters $filters): array;

    public function countByProjectIdAndFilters(ProjectId $projectId, Filters $filters): int;
}
