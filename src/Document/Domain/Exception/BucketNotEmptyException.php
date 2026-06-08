<?php

declare(strict_types=1);

namespace App\Document\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class BucketNotEmptyException extends DomainException
{
    public static function withName(string $bucket): self
    {
        return new self(sprintf('Bucket "%s" is not empty.', $bucket));
    }

    public function errorCode(): string
    {
        return 'document.bucket_not_empty';
    }
}
