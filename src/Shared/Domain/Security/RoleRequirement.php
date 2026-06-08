<?php

declare(strict_types=1);

namespace App\Shared\Domain\Security;

final readonly class RoleRequirement
{
    /**
     * @param list<string> $roles
     */
    private function __construct(
        public array $roles,
        public RoleMatchMode $mode,
    ) {
    }

    public static function any(string ...$roles): self
    {
        return new self(array_values($roles), RoleMatchMode::Any);
    }

    public static function all(string ...$roles): self
    {
        return new self(array_values($roles), RoleMatchMode::All);
    }

    public static function authenticated(): self
    {
        return new self([], RoleMatchMode::Any);
    }

    public static function user(): self
    {
        return self::any('ROLE_USER');
    }

    public static function admin(): self
    {
        return self::any('ROLE_ADMIN');
    }
}
