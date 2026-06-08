<?php

declare(strict_types=1);

namespace App\Document\Application\Command\DeleteBucket;

final class DeleteBucketResult
{
    public function __construct(
        public readonly string $name,
    ) {
    }
}
