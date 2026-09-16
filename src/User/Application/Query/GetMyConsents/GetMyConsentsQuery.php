<?php

declare(strict_types=1);

namespace App\User\Application\Query\GetMyConsents;

use App\Shared\Domain\Bus\Query\Query;
use App\Shared\Domain\Security\RoleRequirement;
use App\User\Application\Security\AuthorizedMessage;

/** @implements Query<ConsentsResponse> */
final class GetMyConsentsQuery implements Query, AuthorizedMessage
{
    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::authenticated();
    }
}
