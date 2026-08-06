<?php

declare(strict_types=1);

namespace App\Shared\Domain\Filter;

final class CursorPagination
{
    public function __construct(
        public readonly ?Cursor $after,
        public readonly int $limit,
    ) {
    }

    public static function fromRequest(?string $cursorToken, int $limit): self
    {
        return new self(
            after: (null !== $cursorToken && '' !== $cursorToken) ? Cursor::decode($cursorToken) : null,
            limit: min(100, max(1, $limit)),
        );
    }
}
