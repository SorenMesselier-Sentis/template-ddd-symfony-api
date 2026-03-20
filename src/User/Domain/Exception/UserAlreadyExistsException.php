<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\AlreadyExistsException;

final class UserAlreadyExistsException extends AlreadyExistsException
{
    public static function withEmail(string $email): self
    {
        return new self(sprintf('User with email "%s" already exists.', $email));
    }

    public function errorCode(): string
    {
        return 'user.already_exists';
    }
}
