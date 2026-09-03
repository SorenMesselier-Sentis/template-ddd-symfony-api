<?php

declare(strict_types=1);

namespace App\ApiClient\Domain\Repository;

use App\ApiClient\Domain\Entity\IssuedAccessToken;

interface IssuedAccessTokenRepositoryInterface
{
    public function save(IssuedAccessToken $token): void;

    public function findById(string $id): ?IssuedAccessToken;

    public function revoke(string $id): void;

    public function revokeAllForClient(string $apiClientId): void;

    /**
     * Removes every access token whose `expiresAt` is strictly earlier than `$now`.
     *
     * @return int number of rows deleted
     */
    public function deleteExpired(\DateTimeImmutable $now): int;
}
