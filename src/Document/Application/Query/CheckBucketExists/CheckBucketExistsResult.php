<?php

declare(strict_types=1);

namespace App\Document\Application\Query\CheckBucketExists;

final class CheckBucketExistsResult
{
    public function __construct(
        public readonly string $name,
        public readonly bool $exists,
    ) {
    }
}
