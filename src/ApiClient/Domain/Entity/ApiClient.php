<?php

declare(strict_types=1);

namespace App\ApiClient\Domain\Entity;

use App\ApiClient\Domain\Event\ApiClientCreated;
use App\ApiClient\Domain\Event\ApiClientDeleted;
use App\ApiClient\Domain\Event\ApiClientRevoked;
use App\ApiClient\Domain\Event\ApiClientSecretRotated;
use App\ApiClient\Domain\ValueObject\ApiClientId;
use App\ApiClient\Domain\ValueObject\ApiClientStatus;
use App\ApiClient\Domain\ValueObject\HashedClientSecret;
use App\Shared\Domain\Bus\Event\DomainEvent;

final class ApiClient
{
    /** @var DomainEvent[] */
    private array $domainEvents = [];

    /**
     * @param list<string> $scopes
     */
    private function __construct(
        private readonly ApiClientId $id,
        private string $name,
        private HashedClientSecret $secretHash,
        private array $scopes,
        private ApiClientStatus $status,
        private readonly \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $lastUsedAt,
    ) {
    }

    /**
     * @param list<string> $scopes
     */
    public static function create(
        ApiClientId $id,
        string $name,
        HashedClientSecret $secretHash,
        array $scopes,
    ): self {
        $entity = new self(
            id: $id,
            name: $name,
            secretHash: $secretHash,
            scopes: $scopes,
            status: ApiClientStatus::ACTIVE,
            createdAt: new \DateTimeImmutable(),
            lastUsedAt: null,
        );

        $entity->record(new ApiClientCreated(aggregateId: $id->value()));

        return $entity;
    }

    public function rotateSecret(HashedClientSecret $secretHash): void
    {
        $this->secretHash = $secretHash;
        $this->record(new ApiClientSecretRotated(aggregateId: $this->id->value()));
    }

    public function revoke(): void
    {
        $this->status = ApiClientStatus::REVOKED;
        $this->record(new ApiClientRevoked(aggregateId: $this->id->value()));
    }

    public function delete(): void
    {
        $this->status = ApiClientStatus::DELETED;
        $this->record(new ApiClientDeleted(aggregateId: $this->id->value()));
    }

    public function recordUsage(): void
    {
        $this->lastUsedAt = new \DateTimeImmutable();
    }

    public function isActive(): bool
    {
        return ApiClientStatus::ACTIVE === $this->status;
    }

    /**
     * @return DomainEvent[]
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    private function record(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    public function id(): ApiClientId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function secretHash(): HashedClientSecret
    {
        return $this->secretHash;
    }

    /**
     * @return list<string>
     */
    public function scopes(): array
    {
        return $this->scopes;
    }

    public function status(): ApiClientStatus
    {
        return $this->status;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function lastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }
}
