<?php

declare(strict_types=1);

namespace App\Document\Domain\Exception;

use App\Shared\Domain\Exception\InvalidArgumentException;

final class InvalidObjectPathException extends InvalidArgumentException
{
    public static function withReason(string $reason): self
    {
        return new self(sprintf('Invalid object path: %s', $reason));
    }

    public function errorCode(): string
    {
        return 'document.invalid_object_path';
    }
}
