<?php

declare(strict_types=1);

namespace App\Document\Application\Query\CheckBucketExists;

use App\Shared\Domain\Bus\Query\Response;

final class CheckBucketExistsResult implements Response
{
    public function __construct(
        public readonly string $name,
        public readonly bool $exists,
    ) {
    }
}
