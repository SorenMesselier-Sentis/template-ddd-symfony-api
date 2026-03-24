<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

final class MissingFieldException extends InvalidArgumentException
{
    public function errorCode(): string
    {
        return 'missing_field';
    }
}
