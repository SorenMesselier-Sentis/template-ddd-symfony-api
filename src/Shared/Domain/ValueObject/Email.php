<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

final class Email
{
    private readonly string $value;

    public function __construct(string $value)
    {
        $this->ensureIsValid($value);
        $this->value = mb_strtolower(trim($value));
    }

    public static function fromString(string $value): self
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
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(
                sprintf('"%s" is not a valid email address.', $value)
            );
        }

        if (mb_strlen($value) > 254) {
            throw new \InvalidArgumentException(
                sprintf('Email address "%s" exceeds the maximum length of 254 characters.', $value)
            );
        }
    }
}
