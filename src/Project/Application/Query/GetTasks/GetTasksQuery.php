<?php

declare(strict_types=1);

namespace App\Project\Application\Query\GetTasks;

use App\Project\Application\Security\AuthorizedMessage;
use App\Shared\Domain\Bus\Query\Query;
use App\Shared\Domain\Filter\Filters;
use App\Shared\Domain\Security\RoleRequirement;

/** @implements Query<TasksResponse> */
final class GetTasksQuery implements Query, AuthorizedMessage
{
    public function __construct(
        public readonly string $projectId,
        public readonly Filters $filters,
    ) {
    }

    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::user();
    }
}
