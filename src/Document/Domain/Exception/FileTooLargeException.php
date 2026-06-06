<?php

declare(strict_types=1);

namespace App\Document\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class FileTooLargeException extends DomainException
{
    public static function exceedsMaximum(int $maxBytes): self
    {
        $maxMb = (int) ($maxBytes / 1024 / 1024);

        return new self(sprintf('File size exceeds the maximum allowed size of %d MB.', $maxMb));
    }

    public function errorCode(): string
    {
        return 'document.file_too_large';
    }
}
