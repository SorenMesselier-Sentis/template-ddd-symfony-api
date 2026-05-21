<?php

declare(strict_types=1);

namespace App\User\Application\Security;

use App\User\Domain\ValueObject\UserRole;

final readonly class RoleRequirement
{
    /**
     * @param list<UserRole> $roles
     */
    private function __construct(
        public array $roles,
        public RoleMatchMode $mode,
    ) {
    }

    public static function any(UserRole ...$roles): self
    {
        return new self(array_values($roles), RoleMatchMode::Any);
    }

    public static function all(UserRole ...$roles): self
    {
        return new self(array_values($roles), RoleMatchMode::All);
    }

    public static function authenticated(): self
    {
        return new self([], RoleMatchMode::Any);
    }

    public static function admin(): self
    {
        return self::any(UserRole::ADMIN);
    }
}
