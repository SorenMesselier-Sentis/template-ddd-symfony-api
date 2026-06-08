<?php

declare(strict_types=1);

namespace App\Document\Domain\Exception;

use App\Shared\Domain\Exception\AlreadyExistsException;

final class BucketAlreadyExistsException extends AlreadyExistsException
{
    public static function withName(string $bucket): self
    {
        return new self(sprintf('Bucket "%s" already exists.', $bucket));
    }

    public function errorCode(): string
    {
        return 'document.bucket_already_exists';
    }
}
