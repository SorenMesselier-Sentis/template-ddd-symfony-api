<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Pagination;

use Symfony\Component\HttpFoundation\Request;

final class PaginationLinkBuilder
{
    /**
     * @return array<string, string|null>
     */
    public function build(Request $request, int $page, int $limit, int $totalPages): array
    {
        return [
            'self' => $this->link($request, $page, $limit),
            'next' => $page < $totalPages ? $this->link($request, $page + 1, $limit) : null,
            'previous' => $page > 1 ? $this->link($request, $page - 1, $limit) : null,
        ];
    }

    private function link(Request $request, int $page, int $limit): string
    {
        $query = $request->query->all();
        unset($query['per_page'], $query['page'], $query['limit']);
        $query['page'] = $page;
        $query['limit'] = $limit;

        return $this->normalizePath($request->getPathInfo()).'?'.http_build_query($query);
    }

    private function normalizePath(string $pathInfo): string
    {
        if (str_starts_with($pathInfo, '/api/')) {
            return substr($pathInfo, 4);
        }

        return $pathInfo;
    }
}
