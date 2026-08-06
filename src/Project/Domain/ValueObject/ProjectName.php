<?php

declare(strict_types=1);

namespace App\Project\Domain\ValueObject;

use App\Project\Domain\Exception\InvalidProjectNameException;

final class ProjectName
{
    private readonly string $value;

    public function __construct(string $value)
    {
        $value = trim($value);
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
        if ('' === $value) {
            throw InvalidProjectNameException::withReason('cannot be empty.');
        }

        if (mb_strlen($value) > 100) {
            throw InvalidProjectNameException::withReason('cannot exceed 100 characters.');
        }
    }
}
