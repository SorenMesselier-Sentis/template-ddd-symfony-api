<?php

declare(strict_types=1);

namespace App\Document\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class InvalidPresignedUrlTtlException extends DomainException
{
    public static function outOfRange(int $minTtl, int $maxTtl): self
    {
        return new self(sprintf(
            'Presigned URL TTL must be between %d and %d seconds.',
            $minTtl,
            $maxTtl,
        ));
    }

    public function errorCode(): string
    {
        return 'document.invalid_presigned_url_ttl';
    }
}
