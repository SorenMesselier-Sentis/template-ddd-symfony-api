<?php

declare(strict_types=1);

namespace App\Project\Domain\Entity;

use App\Project\Domain\Event\ProjectCreated;
use App\Project\Domain\Event\ProjectDeleted;
use App\Project\Domain\Event\ProjectReplaced;
use App\Project\Domain\Event\ProjectUpdated;
use App\Project\Domain\ValueObject\OwnerId;
use App\Project\Domain\ValueObject\ProjectId;
use App\Project\Domain\ValueObject\ProjectName;
use App\Project\Domain\ValueObject\ProjectStatus;
use App\Shared\Domain\Bus\Event\DomainEvent;

final class Project
{
    /** @var DomainEvent[] */
    private array $domainEvents = [];

    private function __construct(
        private readonly ProjectId $id,
        private readonly OwnerId $ownerId,
        private ProjectName $name,
        private ProjectStatus $status,
        private readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(ProjectId $id, OwnerId $ownerId, ProjectName $name): self
    {
        $now = new \DateTimeImmutable();
        $entity = new self($id, $ownerId, $name, ProjectStatus::ACTIVE, $now, $now);

        $entity->record(new ProjectCreated(aggregateId: $id->value()));

        return $entity;
    }

    /**
     * Partial update (PATCH): rename and/or transition between ACTIVE/ARCHIVED.
     * Deletion always goes through delete(), never through this generic transition.
     */
    public function update(?ProjectName $name, ?ProjectStatus $status): void
    {
        if (null !== $name) {
            $this->name = $name;
        }

        if (null !== $status && ProjectStatus::DELETED !== $status) {
            $this->status = $status;
        }

        $this->touch();
        $this->record(new ProjectUpdated(aggregateId: $this->id->value()));
    }

    /**
     * Full replace (PUT): mutable fields only — status is untouched.
     */
    public function replace(ProjectName $name): void
    {
        $this->name = $name;
        $this->touch();
        $this->record(new ProjectReplaced(aggregateId: $this->id->value()));
    }

    public function delete(): void
    {
        $this->status = ProjectStatus::DELETED;
        $this->touch();
        $this->record(new ProjectDeleted(aggregateId: $this->id->value()));
    }

    public function isActive(): bool
    {
        return ProjectStatus::ACTIVE === $this->status;
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

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function id(): ProjectId
    {
        return $this->id;
    }

    public function ownerId(): OwnerId
    {
        return $this->ownerId;
    }

    public function name(): ProjectName
    {
        return $this->name;
    }

    public function status(): ProjectStatus
    {
        return $this->status;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
