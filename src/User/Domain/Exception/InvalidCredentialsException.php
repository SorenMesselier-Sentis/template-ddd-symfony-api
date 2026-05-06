<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\UnauthorizedException;

final class InvalidCredentialsException extends UnauthorizedException
{
    public static function create(): self
    {
        return new self('Invalid credentials.');
    }

    public function errorCode(): string
    {
        return 'user.invalid_credentials';
    }
}
