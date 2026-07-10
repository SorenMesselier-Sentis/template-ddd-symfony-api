<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\UnauthorizedException;

final class InvalidPasswordResetTokenException extends UnauthorizedException
{
    public static function create(): self
    {
        return new self('The password reset token is invalid or has expired.');
    }

    public function errorCode(): string
    {
        return 'user.invalid_password_reset_token';
    }
}
