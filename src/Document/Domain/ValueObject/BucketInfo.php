<?php

declare(strict_types=1);

namespace App\Document\Domain\ValueObject;

final readonly class BucketInfo
{
    public function __construct(
        public BucketName $name,
        public \DateTimeImmutable $createdAt,
    ) {
    }
}
