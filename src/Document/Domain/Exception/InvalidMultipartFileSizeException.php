<?php

declare(strict_types=1);

namespace App\Document\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class InvalidMultipartFileSizeException extends DomainException
{
    public static function outOfRange(int $minBytes, int $maxBytes): self
    {
        $minMb = (int) ($minBytes / 1024 / 1024);
        $maxGb = (int) ($maxBytes / 1024 / 1024 / 1024);

        return new self(sprintf(
            'Multipart upload file size must be between %d MB and %d GB.',
            $minMb,
            $maxGb,
        ));
    }

    public function errorCode(): string
    {
        return 'document.invalid_multipart_file_size';
    }
}
