<?php

declare(strict_types=1);

namespace App\Document\Domain\Exception;

use App\Shared\Domain\Exception\InvalidArgumentException;

final class InvalidPresignedUrlException extends InvalidArgumentException
{
    public static function empty(): self
    {
        return new self('Presigned URL cannot be empty.');
    }

    public function errorCode(): string
    {
        return 'document.invalid_presigned_url';
    }
}
