<?php

declare(strict_types=1);

namespace App\Document\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class InvalidPartSizeException extends DomainException
{
    public static function empty(): self
    {
        return new self('Part size must be greater than 0 bytes.');
    }

    public static function belowMinimum(int $minBytes): self
    {
        $minMb = (int) ($minBytes / 1024 / 1024);

        return new self(sprintf('Non-final parts must be at least %d MB.', $minMb));
    }

    public function errorCode(): string
    {
        return 'document.invalid_part_size';
    }
}
