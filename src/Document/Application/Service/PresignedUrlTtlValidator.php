<?php

declare(strict_types=1);

namespace App\Document\Application\Service;

use App\Document\Domain\Exception\InvalidPresignedUrlTtlException;

final class PresignedUrlTtlValidator
{
    public function __construct(
        private readonly int $minTtlSeconds,
        private readonly int $maxTtlSeconds,
    ) {
    }

    public function validate(int $ttlSeconds): void
    {
        if ($ttlSeconds < $this->minTtlSeconds || $ttlSeconds > $this->maxTtlSeconds) {
            throw InvalidPresignedUrlTtlException::outOfRange($this->minTtlSeconds, $this->maxTtlSeconds);
        }
    }
}
