<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use Symfony\Component\Uid\Uuid as SymfonyUuid;

abstract class Uuid
{
    private readonly string $value;

    public function __construct(string $value)
    {
        $this->ensureIsValid($value);
        $this->value = $value;
    }

    public function fromString(string $value): static
    {
        return new static($value);
    }

    public function random(): static
    {
        return new static(SymfonyUuid::v4()->toRfc4122());
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function ensureIsValid(string $value): void
    {
        if (!SymfonyUuid::isValid($value)) {
            throw new \InvalidArgumentException(
            sprintf('"%s" is not a valid UUID.', $value)
            );
        }
    }
}
