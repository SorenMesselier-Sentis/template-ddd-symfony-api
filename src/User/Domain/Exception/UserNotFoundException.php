<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;

final class UserNotFoundException extends NotFoundException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('User with "%s" was not found.', $id));
    }

    public static function withEmail(string $email): self
    {
        return new self(sprintf('User with email "%s" was not found.', $email));
    }

    public function errorCode(): string
    {
        return 'user.not_found';
    }
}
