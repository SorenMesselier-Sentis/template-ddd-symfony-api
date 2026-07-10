<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\User\Domain\Entity\PasswordResetToken as PasswordResetTokenEntity;

interface PasswordResetTokenRepositoryInterface
{
    public function save(PasswordResetTokenEntity $token): void;

    public function findByToken(string $token): ?PasswordResetTokenEntity;

    public function revokeAllForUser(string $userId): void;

    public function deleteExpired(\DateTimeImmutable $now): int;
}
