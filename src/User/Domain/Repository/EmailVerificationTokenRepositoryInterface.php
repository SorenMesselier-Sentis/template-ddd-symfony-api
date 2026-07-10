<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\User\Domain\Entity\EmailVerificationToken as EmailVerificationTokenEntity;

interface EmailVerificationTokenRepositoryInterface
{
    public function save(EmailVerificationTokenEntity $token): void;

    public function findByToken(string $token): ?EmailVerificationTokenEntity;

    public function revokeAllForUser(string $userId): void;

    public function deleteExpired(\DateTimeImmutable $now): int;
}
