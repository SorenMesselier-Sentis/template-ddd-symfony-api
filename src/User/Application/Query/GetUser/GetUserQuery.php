<?php

declare(strict_types=1);

namespace App\User\Application\Query\GetUser;

use App\Shared\Domain\Bus\Query\Query;
use App\User\Application\Security\AuthorizedMessage;
use App\Shared\Domain\Security\RoleRequirement;

final class GetUserQuery implements Query, AuthorizedMessage
{
    public function __construct(
        public readonly string $id,
    ) {
    }

    public function roleRequirement(): RoleRequirement
    {
        return RoleRequirement::authenticated();
    }
}
