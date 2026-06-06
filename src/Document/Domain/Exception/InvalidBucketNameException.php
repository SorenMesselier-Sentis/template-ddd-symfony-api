<?php

declare(strict_types=1);

namespace App\Document\Domain\Exception;

use App\Shared\Domain\Exception\InvalidArgumentException;

final class InvalidBucketNameException extends InvalidArgumentException
{
    public static function withReason(string $reason): self
    {
        return new self(sprintf('Invalid bucket name: %s', $reason));
    }

    public function errorCode(): string
    {
        return 'document.invalid_bucket_name';
    }
}
