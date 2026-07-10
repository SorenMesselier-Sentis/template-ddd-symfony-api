<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\InvalidArgumentException;

final class WeakPasswordException extends InvalidArgumentException
{
    public static function tooShort(int $minLength): self
    {
        return new self(sprintf('Password must be at least %d characters long.', $minLength));
    }

    public function errorCode(): string
    {
        return 'user.weak_password';
    }
}
