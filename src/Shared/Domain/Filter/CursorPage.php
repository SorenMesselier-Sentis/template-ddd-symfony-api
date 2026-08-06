<?php

declare(strict_types=1);

namespace App\Shared\Domain\Filter;

/**
 * @template T
 */
final class CursorPage
{
    /**
     * @param list<T> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly ?string $nextCursor,
    ) {
    }
}
