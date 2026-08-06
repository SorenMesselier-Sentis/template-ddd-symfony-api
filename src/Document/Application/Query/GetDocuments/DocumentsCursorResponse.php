<?php

declare(strict_types=1);

namespace App\Document\Application\Query\GetDocuments;

use App\Shared\Domain\Bus\Query\Response;

final class DocumentsCursorResponse implements Response
{
    /**
     * @param list<DocumentItemResponse> $documents
     */
    public function __construct(
        public readonly array $documents,
        public readonly int $limit,
        public readonly bool $hasMore,
        public readonly ?string $nextCursor,
    ) {
    }
}
