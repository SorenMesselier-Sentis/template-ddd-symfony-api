<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

use App\User\Domain\Exception\InvalidTokenException;

final class TokenClaims
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        public readonly string $sub,
        public readonly string $email,
        public readonly array $roles,
        public readonly int $iat,
        public readonly int $exp,
    ) {
        if ('' === $this->sub) {
            throw InvalidTokenException::create();
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromAccessTokenPayload(array $payload): self
    {
        return new self(
            sub: self::requireString($payload, 'sub'),
            email: self::requireString($payload, 'email'),
            roles: self::requireStringList($payload, 'roles'),
            iat: self::requireInt($payload, 'iat'),
            exp: self::requireInt($payload, 'exp'),
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromRefreshTokenPayload(array $payload): self
    {
        return new self(
            sub: self::requireString($payload, 'sub'),
            email: self::optionalString($payload, 'email', ''),
            roles: self::optionalStringList($payload, 'roles'),
            iat: self::optionalInt($payload, 'iat', 0),
            exp: self::optionalInt($payload, 'exp', 0),
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function requireString(array $payload, string $claim): string
    {
        $value = $payload[$claim] ?? null;

        if (!\is_string($value) || '' === $value) {
            throw InvalidTokenException::create();
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function optionalString(array $payload, string $claim, string $default): string
    {
        if (!\array_key_exists($claim, $payload)) {
            return $default;
        }

        $value = $payload[$claim];

        if (!\is_string($value)) {
            throw InvalidTokenException::create();
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function requireInt(array $payload, string $claim): int
    {
        $value = $payload[$claim] ?? null;

        if (!\is_int($value)) {
            throw InvalidTokenException::create();
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function optionalInt(array $payload, string $claim, int $default): int
    {
        if (!\array_key_exists($claim, $payload)) {
            return $default;
        }

        $value = $payload[$claim];

        if (!\is_int($value)) {
            throw InvalidTokenException::create();
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return list<string>
     */
    private static function requireStringList(array $payload, string $claim): array
    {
        $value = $payload[$claim] ?? null;

        if (!\is_array($value)) {
            throw InvalidTokenException::create();
        }

        return self::normalizeStringList($value);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return list<string>
     */
    private static function optionalStringList(array $payload, string $claim): array
    {
        if (!\array_key_exists($claim, $payload)) {
            return [];
        }

        $value = $payload[$claim];

        if (!\is_array($value)) {
            throw InvalidTokenException::create();
        }

        return self::normalizeStringList($value);
    }

    /**
     * @param array<mixed, mixed> $value
     *
     * @return list<string>
     */
    private static function normalizeStringList(array $value): array
    {
        $roles = [];

        foreach ($value as $role) {
            if (!\is_string($role) || '' === $role) {
                throw InvalidTokenException::create();
            }

            $roles[] = $role;
        }

        return $roles;
    }
}
