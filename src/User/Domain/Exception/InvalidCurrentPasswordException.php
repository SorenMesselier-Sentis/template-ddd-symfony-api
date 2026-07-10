<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\UnauthorizedException;

final class InvalidCurrentPasswordException extends UnauthorizedException
{
    public static function create(): self
    {
        return new self('The current password is incorrect.');
    }

    public function errorCode(): string
    {
        return 'user.invalid_current_password';
    }
}
