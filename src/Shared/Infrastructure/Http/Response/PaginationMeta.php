<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Response;

final class PaginationMeta
{
    /**
     * @return array<string, bool|int>
     */
    public static function from(int $total, int $page, int $limit): array
    {
        $totalPages = self::totalPages($total, $limit);

        return [
            'page' => $page,
            'limit' => $limit,
            'total_items' => $total,
            'total_pages' => $totalPages,
            'has_next' => $page < $totalPages,
            'has_previous' => $page > 1,
        ];
    }

    public static function totalPages(int $total, int $limit): int
    {
        if (0 === $total) {
            return 0;
        }

        return (int) ceil($total / $limit);
    }
}
