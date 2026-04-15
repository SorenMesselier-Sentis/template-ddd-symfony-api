<?php

declare(strict_types=1);

namespace App\User\Application\Query\GetUsers;

use App\Shared\Domain\Bus\Query\Query;

final class GetUsersQuery implements Query
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 20,
    ) {}
}
