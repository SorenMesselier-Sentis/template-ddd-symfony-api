<?php

declare(strict_types=1);

namespace App\User\Application\Query\GetUsers;

use App\Shared\Domain\Bus\Query\Response;
use App\User\Application\Query\GetUsers\UserItemResponse;

final class UsersResponse implements Response
{
    /**
     * @param array<UserItemResponse> $users
     */
    public function __construct(
        public readonly array $users,
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage,
    ) {}
}
