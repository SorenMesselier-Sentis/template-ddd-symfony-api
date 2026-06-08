<?php

declare(strict_types=1);

namespace App\Document\Application\Service;

use App\Document\Domain\Exception\InvalidMimeTypeException;
use App\Document\Domain\ValueObject\BucketName;
use App\Document\Domain\ValueObject\MimeType;

final class BucketMimeTypeValidator
{
    /**
     * @param array<string, array{allowed_mime_types: list<string>}> $buckets
     */
    public function __construct(
        private readonly array $buckets,
    ) {
    }

    public function validate(BucketName $bucket, MimeType $mimeType): void
    {
        $bucketConfig = $this->buckets[$bucket->value()] ?? null;

        if (null === $bucketConfig) {
            throw InvalidMimeTypeException::notAllowed($mimeType->value(), $bucket->value());
        }

        if (!in_array($mimeType->value(), $bucketConfig['allowed_mime_types'], true)) {
            throw InvalidMimeTypeException::notAllowed($mimeType->value(), $bucket->value());
        }
    }
}
