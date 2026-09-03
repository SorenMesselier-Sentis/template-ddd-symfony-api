<?php

declare(strict_types=1);

namespace App\ApiClient\Application\Query\GetApiClientsCollection;

use App\Shared\Domain\Bus\Query\Response;

final class ApiClientsResponse implements Response
{
    /**
     * @param list<ApiClientItemResponse> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $limit,
    ) {
    }
}
