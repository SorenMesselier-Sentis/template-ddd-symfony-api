<?php

declare(strict_types=1);

namespace App\User\Application\Query\GetUsers;

use App\Shared\Domain\Bus\Query\Query;
use App\Shared\Domain\Filter\Filters;

final class GetUsersQuery implements Query
{
    public function __construct(
        public readonly Filters $filters,
    ) {
    }
}
