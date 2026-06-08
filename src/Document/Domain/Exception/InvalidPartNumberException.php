<?php

declare(strict_types=1);

namespace App\Document\Domain\Exception;

use App\Shared\Domain\Exception\InvalidArgumentException;

final class InvalidPartNumberException extends InvalidArgumentException
{
    public static function outOfRange(int $value, int $min, int $max): self
    {
        return new self(sprintf('Part number %d is out of range (%d-%d).', $value, $min, $max));
    }

    public function errorCode(): string
    {
        return 'document.invalid_part_number';
    }
}
