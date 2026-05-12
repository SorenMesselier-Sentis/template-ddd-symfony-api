<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\UnauthorizedException;

final class TokenExpiredException extends UnauthorizedException
{
    public static function create(): self
    {
        return new self('The access token has expired');
    }

    public function errorCode(): string
    {
        return 'authentication.token_expired';
    }
}
