<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\UnauthorizedException;

final class InvalidTokenException extends UnauthorizedException
{
    public static function create(): self
    {
        return new self('The access token is invalid');
    }

    public function errorCode(): string
    {
        return 'authentication.invalid_token';
    }
}
