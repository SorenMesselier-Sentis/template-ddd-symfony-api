<?php

declare(strict_types=1);

namespace App\Project\Application\Query\GetTask;

use App\Project\Application\Security\AuthorizedMessage;
use App\Shared\Domain\Bus\Query\Query;
use App\Shared\Domain\Security\RoleRequirement;

/** @implements Query<TaskResponse> */
final class GetTaskQuery implements Query, AuthorizedMessage
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
