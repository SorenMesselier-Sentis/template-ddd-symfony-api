<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\InvalidArgumentException;

final class EmailAlreadyVerifiedException extends InvalidArgumentException
{
    public static function create(): self
    {
        return new self('Email address is already verified.');
    }

    public function errorCode(): string
    {
        return 'user.email_already_verified';
    }
}
