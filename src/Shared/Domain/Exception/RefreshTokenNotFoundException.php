<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

final class RefreshTokenNotFoundException extends NotFoundException
{
    public static function create(): self
    {
        return new self('Refresh was not found');
    }

    public function errorCode(): string
    {
        return 'not_found';
    }
}
