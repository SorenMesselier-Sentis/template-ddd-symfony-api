<?php

declare(strict_types=1);

namespace App\Document\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;

final class BucketNotFoundException extends NotFoundException
{
    public static function withName(string $bucket): self
    {
        return new self(sprintf('Bucket "%s" was not found.', $bucket));
    }

    public function errorCode(): string
    {
        return 'document.bucket_not_found';
    }
}
