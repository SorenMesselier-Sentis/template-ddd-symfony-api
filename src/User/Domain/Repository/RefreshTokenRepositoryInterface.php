<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\User\Domain\Entity\RefreshToken as RefreshTokenEntity;

interface RefreshTokenRepositoryInterface
{
    public function save(RefreshTokenEntity $token): void;

    public function findByToken(string $token): ?RefreshTokenEntity;

    public function revokeAllForUser(string $userId): void;
}
