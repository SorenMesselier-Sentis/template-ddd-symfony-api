<?php

declare(strict_types=1);

namespace App\Project\Domain\ValueObject;

use App\Project\Domain\Exception\InvalidTaskTitleException;

final class TaskTitle
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
            throw InvalidTaskTitleException::withReason('cannot be empty.');
        }

        if (mb_strlen($value) > 200) {
            throw InvalidTaskTitleException::withReason('cannot exceed 200 characters.');
        }
    }
}
