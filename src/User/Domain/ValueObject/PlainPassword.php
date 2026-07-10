<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

use App\User\Domain\Exception\WeakPasswordException;

final class PlainPassword
{
    private const MIN_LENGTH = 8;

    private function __construct(private readonly string $value)
    {
    }

    public static function fromString(string $value): self
    {
        if (strlen($value) < self::MIN_LENGTH) {
            throw WeakPasswordException::tooShort(self::MIN_LENGTH);
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
