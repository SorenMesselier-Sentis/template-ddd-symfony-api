<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

final class InvalidJsonException extends InvalidArgumentException
{
    public function errorCode(): string
    {
        return 'invalid_json';
    }
}
