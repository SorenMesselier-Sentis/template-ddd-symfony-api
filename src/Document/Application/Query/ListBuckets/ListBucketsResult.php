<?php

declare(strict_types=1);

namespace App\Document\Application\Query\ListBuckets;

final class ListBucketsResult
{
    /**
     * @param list<array{name: string, createdAt: string}> $buckets
     */
    public function __construct(
        public readonly array $buckets,
    ) {
    }
}
