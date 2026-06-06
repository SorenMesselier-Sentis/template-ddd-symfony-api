<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

final class UnauthenticatedException extends UnauthorizedException
{
    public static function create(): self
    {
        return new self('Authentication is required.');
    }

    public function errorCode(): string
    {
        return 'unauthenticated';
    }
}
