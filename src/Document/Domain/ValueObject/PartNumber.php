<?php

declare(strict_types=1);

namespace App\Document\Domain\ValueObject;

use App\Document\Domain\Exception\InvalidPartNumberException;

final class PartNumber
{
    private const MIN = 1;
    private const MAX = 10000;

    private readonly int $value;

    public function __construct(int $value)
    {
        $this->ensureIsValid($value);
        $this->value = $value;
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    public function value(): int
    {
        return $this->value;
    }

    private function ensureIsValid(int $value): void
    {
        if ($value < self::MIN || $value > self::MAX) {
            throw InvalidPartNumberException::outOfRange($value, self::MIN, self::MAX);
        }
    }
}
