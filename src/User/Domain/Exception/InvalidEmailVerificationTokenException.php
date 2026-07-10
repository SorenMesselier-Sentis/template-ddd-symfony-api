<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\UnauthorizedException;

final class InvalidEmailVerificationTokenException extends UnauthorizedException
{
    public static function create(): self
    {
        return new self('The email verification token is invalid or has expired.');
    }

    public function errorCode(): string
    {
        return 'user.invalid_email_verification_token';
    }
}
