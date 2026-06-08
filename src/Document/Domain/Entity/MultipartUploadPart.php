<?php

declare(strict_types=1);

namespace App\Document\Domain\Entity;

use App\Document\Domain\ValueObject\PartNumber;

final class MultipartUploadPart
{
    public function __construct(
        private readonly PartNumber $partNumber,
        private readonly string $etag,
        private readonly int $size,
    ) {
    }

    public function partNumber(): PartNumber
    {
        return $this->partNumber;
    }

    public function etag(): string
    {
        return $this->etag;
    }

    public function size(): int
    {
        return $this->size;
    }
}
