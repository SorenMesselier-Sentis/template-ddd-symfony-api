<?php

declare(strict_types=1);

namespace App\Project\Application\Query\GetProject;

use App\Project\Application\Security\AuthorizedMessage;
use App\Shared\Domain\Bus\Query\Query;
use App\Shared\Domain\Security\RoleRequirement;

/** @implements Query<ProjectResponse> */
final class GetProjectQuery implements Query, AuthorizedMessage
{
    public function __construct(
        public readonly string $id,
    ) {
    }

    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::user();
    }
}
