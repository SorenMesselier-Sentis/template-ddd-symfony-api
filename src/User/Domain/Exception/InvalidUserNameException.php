<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\InvalidArgumentException;

final class InvalidUserNameException extends InvalidArgumentException
{
    public function errorCode(): string
    {
        return 'user.invalid_name';
    }
}
