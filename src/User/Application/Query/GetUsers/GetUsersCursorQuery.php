<?php

declare(strict_types=1);

namespace App\User\Application\Query\GetUsers;

use App\Shared\Domain\Bus\Query\Query;
use App\Shared\Domain\FeatureFlag\FeatureGatedMessage;
use App\Shared\Domain\Filter\CursorPagination;
use App\Shared\Domain\Filter\Filters;
use App\Shared\Domain\Security\RoleRequirement;
use App\User\Application\Security\AuthorizedMessage;

/** @implements Query<UsersCursorResponse> */
final class GetUsersCursorQuery implements Query, AuthorizedMessage, FeatureGatedMessage
{
    public function __construct(
        public readonly Filters $filters,
        public readonly CursorPagination $cursorPagination,
    ) {
    }

    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::admin();
    }

    public function requiredFeatureFlag(): string
    {
        return 'cursor_pagination';
    }
}
