<?php

declare(strict_types=1);

namespace App\Document\Application\Query\ListBuckets;

use App\Shared\Domain\Bus\Query\Response;

final class ListBucketsResult implements Response
{
    /**
     * @param list<array{name: string, createdAt: string}> $buckets
     */
    public function __construct(
        public readonly array $buckets,
    ) {
    }
}
