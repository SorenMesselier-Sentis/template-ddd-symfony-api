<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

final class TokenClaims
{
    /**
     * @param array<int,mixed> $roles
     */
    public function __construct(
        public readonly string $sub,
        public readonly string $email,
        public readonly array $roles,
        public readonly int $iat,
        public readonly int $exp,
    ) {
    }
}
