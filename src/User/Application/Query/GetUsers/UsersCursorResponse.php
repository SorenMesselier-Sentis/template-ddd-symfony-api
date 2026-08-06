<?php

declare(strict_types=1);

namespace App\User\Application\Query\GetUsers;

use App\Shared\Domain\Bus\Query\Response;

final class UsersCursorResponse implements Response
{
    /**
     * @param array<UserItemResponse> $users
     */
    public function __construct(
        public readonly array $users,
        public readonly int $limit,
        public readonly bool $hasMore,
        public readonly ?string $nextCursor,
    ) {
    }
}
