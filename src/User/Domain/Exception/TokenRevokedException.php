<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\UnauthorizedException;

final class TokenRevokedException extends UnauthorizedException
{
    public static function create(): self
    {
        return new self('The refresh token has been revoked');
    }

    public function errorCode(): string
    {
        return 'authentication.revoked_token';
    }
}
