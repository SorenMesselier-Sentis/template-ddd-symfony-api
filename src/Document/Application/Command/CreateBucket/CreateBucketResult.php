<?php

declare(strict_types=1);

namespace App\Document\Application\Command\CreateBucket;

final class CreateBucketResult
{
    public function __construct(
        public readonly string $name,
        public readonly string $createdAt,
    ) {
    }
}
