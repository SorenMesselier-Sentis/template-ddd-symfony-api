<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

final class ForbiddenException extends UnauthorizedException
{
    public static function create(): self
    {
        return new self('You do not have the permission to access this resource.');
    }

    public function errorCode(): string
    {
        return 'forbidden';
    }
}
