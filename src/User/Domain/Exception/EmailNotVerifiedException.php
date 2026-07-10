<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\UnauthorizedException;

final class EmailNotVerifiedException extends UnauthorizedException
{
    public static function create(): self
    {
        return new self('Email address has not been verified.');
    }

    public function errorCode(): string
    {
        return 'user.email_not_verified';
    }
}
