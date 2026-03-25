<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

use App\User\Domain\Exception\InvalidUserNameException;

final class UserName
{
    private readonly string $value;

    public function __construct(string $value)
    {
        $value = mb_strtolower(trim($value));
        $this->ensureIsValid($value);
        $this->value = trim($value);
    }

    public function fromString(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    private function ensureIsValid(string $value): void
    {
        if ($value === '') {
            throw new InvalidUserNameException('User name cannot be empty.');
        }

        if (mb_strlen($value) < 3) {
            throw new InvalidUserNameException(
                'User name must be at least 3 characters long.'
            );
        }

        if (mb_strlen($value) > 30) {
            throw new InvalidUserNameException(
                'User name cannot exceed 30 characters.'
            );
        }

        if (!preg_match('/^[a-z0-9_]+$/', $value)) {
            throw new InvalidUserNameException(
                'User name can only contain lowercase letters, numbers and underscores.'
            );
        }
    }
}
