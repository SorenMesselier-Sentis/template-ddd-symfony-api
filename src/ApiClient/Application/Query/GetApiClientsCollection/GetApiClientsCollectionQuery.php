<?php

declare(strict_types=1);

namespace App\ApiClient\Application\Query\GetApiClientsCollection;

use App\ApiClient\Application\Security\AuthorizedMessage;
use App\Shared\Domain\Bus\Query\Query;
use App\Shared\Domain\Filter\Filters;
use App\Shared\Domain\Security\RoleRequirement;

/** @implements Query<ApiClientsResponse> */
final class GetApiClientsCollectionQuery implements Query, AuthorizedMessage
{
    public function __construct(
        public readonly Filters $filters,
    ) {
    }

    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::admin();
    }
}
