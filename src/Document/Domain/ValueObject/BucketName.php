<?php

declare(strict_types=1);

namespace App\Document\Domain\ValueObject;

use App\Document\Domain\Exception\InvalidBucketNameException;

final class BucketName
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
        $length = strlen($value);

        if ($length < 3 || $length > 63) {
            throw InvalidBucketNameException::withReason('must be between 3 and 63 characters.');
        }

        if (1 !== preg_match('/^[a-z0-9-]+$/', $value)) {
            throw InvalidBucketNameException::withReason('must contain only lowercase letters, digits and hyphens.');
        }

        if (str_starts_with($value, '-') || str_ends_with($value, '-')) {
            throw InvalidBucketNameException::withReason('must not start or end with a hyphen.');
        }
    }
}
