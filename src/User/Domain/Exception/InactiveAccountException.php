<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\UnauthorizedException;

final class InactiveAccountException extends UnauthorizedException
{
    public static function create(): self
    {
        return new self('This account is inactive.');
    }

    public function errorCode(): string
    {
        return 'user.account_inactive';
    }
}
