<?php

declare(strict_types=1);

namespace App\Shared\Domain\Filter;

final class Pagination
{
    public readonly int $offset;

    public function __construct(
        public readonly int $page = 1,
        public readonly int $limit = 20,
    ) {
        $this->offset = ($page - 1) * $limit;
    }

    public static function fromRequest(int $page, int $limit): self
    {
        return new self(
            page: max(1, $page),
            limit: min(100, max(1, $limit)),
        );
    }
}
