<?php

declare(strict_types=1);

namespace App\ApiClient\Domain\ValueObject;

/**
 * Mirrors {@see \App\User\Domain\ValueObject\HashedPassword}: raw password_hash/password_verify
 * (PASSWORD_ARGON2ID), not Symfony's PasswordHasherInterface — the secret is server-generated
 * (see CreateApiClientCommandHandler), so there is no plain-text VO with strength validation to
 * mirror PlainPassword against; the caller never chooses it.
 */
final class HashedClientSecret
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

    public static function fromPlainSecret(string $plainSecret): self
    {
        return new self(password_hash($plainSecret, PASSWORD_ARGON2ID));
    }

    public function verify(string $plainSecret): bool
    {
        return password_verify($plainSecret, $this->value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
