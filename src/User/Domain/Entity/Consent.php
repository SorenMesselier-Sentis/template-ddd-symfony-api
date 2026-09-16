<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\User\Domain\ValueObject\ConsentId;
use App\User\Domain\ValueObject\ConsentType;
use App\User\Domain\ValueObject\UserId;

final class Consent
{
    private function __construct(
        private readonly ConsentId $id,
        private readonly UserId $userId,
        private readonly ConsentType $type,
        private string $version,
        private \DateTimeImmutable $givenAt,
        private ?\DateTimeImmutable $withdrawnAt,
    ) {
    }

    public static function give(
        ConsentId $id,
        UserId $userId,
        ConsentType $type,
        string $version,
    ): self {
        return new self(
            id: $id,
            userId: $userId,
            type: $type,
            version: $version,
            givenAt: new \DateTimeImmutable(),
            withdrawnAt: null,
        );
    }

    /**
     * Records that consent was (re-)given, e.g. for a newer document version.
     */
    public function renew(string $version): void
    {
        $this->version = $version;
        $this->givenAt = new \DateTimeImmutable();
        $this->withdrawnAt = null;
    }

    public function withdraw(): void
    {
        $this->withdrawnAt = new \DateTimeImmutable();
    }

    public function isActive(): bool
    {
        return null === $this->withdrawnAt;
    }

    public function id(): ConsentId
    {
        return $this->id;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function type(): ConsentType
    {
        return $this->type;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function givenAt(): \DateTimeImmutable
    {
        return $this->givenAt;
    }

    public function withdrawnAt(): ?\DateTimeImmutable
    {
        return $this->withdrawnAt;
    }
}
