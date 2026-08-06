<?php

declare(strict_types=1);

namespace App\User\Application\Query\ExportUserData;

use App\Shared\Domain\Bus\Query\Query;
use App\Shared\Domain\Security\RoleRequirement;
use App\User\Application\Security\AuthorizedMessage;

/** @implements Query<ExportUserDataResponse> */
final class ExportUserDataQuery implements Query, AuthorizedMessage
{
    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::authenticated();
    }
}
