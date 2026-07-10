<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\User\Domain\ValueObject\PasswordResetTokenId;
use App\User\Domain\ValueObject\UserId;

final class PasswordResetToken
{
    public function __construct(
        private readonly PasswordResetTokenId $id,
        private readonly UserId $userId,
        private readonly string $token,
        private readonly \DateTimeImmutable $expiresAt,
        private bool $revoked,
        private readonly \DateTimeImmutable $createdAt,
    ) {
    }

    public static function create(
        PasswordResetTokenId $id,
        UserId $userId,
        string $token,
        \DateTimeImmutable $expiresAt,
    ): self {
        return new self(
            id: $id,
            userId: $userId,
            token: $token,
            expiresAt: $expiresAt,
            revoked: false,
            createdAt: new \DateTimeImmutable(),
        );
    }

    public function revoke(): void
    {
        $this->revoked = true;
    }

    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTimeImmutable();
    }

    public function id(): PasswordResetTokenId
    {
        return $this->id;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function token(): string
    {
        return $this->token;
    }

    public function expiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
