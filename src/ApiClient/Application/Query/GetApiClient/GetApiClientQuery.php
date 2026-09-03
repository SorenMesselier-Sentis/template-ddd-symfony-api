<?php

declare(strict_types=1);

namespace App\ApiClient\Application\Query\GetApiClient;

use App\ApiClient\Application\Security\AuthorizedMessage;
use App\Shared\Domain\Bus\Query\Query;
use App\Shared\Domain\Security\RoleRequirement;

/** @implements Query<ApiClientResponse> */
final class GetApiClientQuery implements Query, AuthorizedMessage
{
    public function __construct(
        public readonly string $id,
    ) {
    }

    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::admin();
    }
}
