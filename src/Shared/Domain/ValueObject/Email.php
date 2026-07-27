<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

final class Email implements StringValueObject
{
    /** @var non-empty-string */
    private readonly string $value;

    public function __construct(string $value)
    {
        $this->value = $this->normalize($value);
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * @return non-empty-string
     */
    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * @return non-empty-string
     */
    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));

        if ('' === $value || false === filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid email address.', $value));
        }

        if (mb_strlen($value) > 254) {
            throw new \InvalidArgumentException(sprintf('Email address "%s" exceeds the maximum length.', $value));
        }

        return $value;
    }
}
