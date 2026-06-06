<?php

declare(strict_types=1);

namespace App\Document\Application\Service;

use App\Document\Domain\Exception\InvalidPartSizeException;

final class MultipartPartSizeValidator
{
    public function __construct(
        private readonly int $minPartBytes,
    ) {
    }

    public function validate(int $size, bool $isLastPart): void
    {
        if ($size <= 0) {
            throw InvalidPartSizeException::empty();
        }

        if (!$isLastPart && $size < $this->minPartBytes) {
            throw InvalidPartSizeException::belowMinimum($this->minPartBytes);
        }
    }
}
