<?php

declare(strict_types=1);

namespace App\Document\Domain\Exception;

use App\Shared\Domain\Exception\InvalidArgumentException;

final class InvalidMimeTypeException extends InvalidArgumentException
{
    public static function notAllowed(string $mimeType, string $bucket): self
    {
        return new self(sprintf('MIME type "%s" is not allowed for bucket "%s".', $mimeType, $bucket));
    }

    public static function invalidFormat(string $mimeType): self
    {
        return new self(sprintf('"%s" is not a valid MIME type.', $mimeType));
    }

    public function errorCode(): string
    {
        return 'document.invalid_mime_type';
    }
}
