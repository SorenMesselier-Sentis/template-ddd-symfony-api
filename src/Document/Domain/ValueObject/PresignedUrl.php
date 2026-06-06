<?php

declare(strict_types=1);

namespace App\Document\Domain\ValueObject;

use App\Document\Domain\Exception\InvalidPresignedUrlException;

final class PresignedUrl
{
    public function __construct(
        private readonly string $url,
        private readonly int $expiresInSeconds,
        private readonly \DateTimeImmutable $expiresAt,
    ) {
        if ('' === $url) {
            throw InvalidPresignedUrlException::empty();
        }
    }

    public function url(): string
    {
        return $this->url;
    }

    public function expiresInSeconds(): int
    {
        return $this->expiresInSeconds;
    }

    public function expiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }
}
