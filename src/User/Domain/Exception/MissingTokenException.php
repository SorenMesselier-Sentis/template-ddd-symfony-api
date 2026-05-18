<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\UnauthorizedException;

final class MissingTokenException extends UnauthorizedException
{
    public static function create(): self
    {
        return new self('Authorization token is missing.');
    }

    public function errorCode(): string
    {
        return 'authentication.missing_token';
    }
}
