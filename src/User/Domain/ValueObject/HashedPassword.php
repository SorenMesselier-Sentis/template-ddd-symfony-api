<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

final class HashedPassword
{
    private readonly string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromHash(string $hash): self
    {
        return new self($hash);
    }

    public static function fromPlainPassword(string $plainPassword): self
    {
        $validated = PlainPassword::fromString($plainPassword);

        return new self(password_hash($validated->value(), PASSWORD_ARGON2ID));
    }

    public function verify(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
