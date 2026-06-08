<?php

declare(strict_types=1);

namespace App\Document\Domain\ValueObject;

use App\Document\Domain\Exception\InvalidMimeTypeException;

final class MimeType
{
    private readonly string $value;

    public function __construct(string $value)
    {
        $value = strtolower(trim($value));
        $this->ensureIsValid($value);
        $this->value = $value;
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
        if (1 !== preg_match('~^[a-z0-9!#$&^_.+-]+/[a-z0-9!#$&^_.+-]+$~', $value)) {
            throw InvalidMimeTypeException::invalidFormat($value);
        }
    }
}
