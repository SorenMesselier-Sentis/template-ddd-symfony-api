<?php

declare(strict_types=1);

namespace App\Project\Application\Query\GetProjects;

use App\Project\Application\Security\AuthorizedMessage;
use App\Shared\Domain\Bus\Query\Query;
use App\Shared\Domain\Filter\Filters;
use App\Shared\Domain\Security\RoleRequirement;

/** @implements Query<ProjectsResponse> */
final class GetProjectsQuery implements Query, AuthorizedMessage
{
    public function __construct(
        public readonly Filters $filters,
    ) {
    }

    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::user();
    }
}
