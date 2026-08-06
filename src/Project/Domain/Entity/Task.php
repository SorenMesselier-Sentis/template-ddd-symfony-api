<?php

declare(strict_types=1);

namespace App\Project\Domain\Entity;

use App\Project\Domain\Event\TaskCreated;
use App\Project\Domain\Event\TaskDeleted;
use App\Project\Domain\Event\TaskReplaced;
use App\Project\Domain\Event\TaskUpdated;
use App\Project\Domain\ValueObject\AssigneeId;
use App\Project\Domain\ValueObject\AttachmentId;
use App\Project\Domain\ValueObject\TaskId;
use App\Project\Domain\ValueObject\TaskStatus;
use App\Project\Domain\ValueObject\TaskTitle;
use App\Shared\Domain\Bus\Event\DomainEvent;

final class Task
{
    /** @var DomainEvent[] */
    private array $domainEvents = [];

    private function __construct(
        private readonly TaskId $id,
        private readonly Project $project,
        private TaskTitle $title,
        private ?AssigneeId $assigneeId,
        private readonly ?AttachmentId $attachmentId,
        private TaskStatus $status,
        private readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(
        TaskId $id,
        Project $project,
        TaskTitle $title,
        ?AssigneeId $assigneeId = null,
        ?AttachmentId $attachmentId = null,
    ): self {
        $now = new \DateTimeImmutable();
        $entity = new self($id, $project, $title, $assigneeId, $attachmentId, TaskStatus::TODO, $now, $now);

        $entity->record(new TaskCreated(aggregateId: $id->value()));

        return $entity;
    }

    /**
     * Partial update (PATCH): rename, reassign and/or transition status.
     * Deletion always goes through delete(), never through this generic transition.
     * There is deliberately no way to *clear* an assignee here — only reassign —
     * since "assigneeId: null" is indistinguishable from "not provided" at the
     * JSON boundary; add an explicit unassign action if you need that.
     */
    public function update(?TaskTitle $title, ?TaskStatus $status, ?AssigneeId $assigneeId): void
    {
        if (null !== $title) {
            $this->title = $title;
        }

        if (null !== $status && TaskStatus::DELETED !== $status) {
            $this->status = $status;
        }

        if (null !== $assigneeId) {
            $this->assigneeId = $assigneeId;
        }

        $this->touch();
        $this->record(new TaskUpdated(aggregateId: $this->id->value()));
    }

    /**
     * Full replace (PUT): mutable fields only — status and attachment are untouched.
     */
    public function replace(TaskTitle $title, ?AssigneeId $assigneeId): void
    {
        $this->title = $title;
        $this->assigneeId = $assigneeId;
        $this->touch();
        $this->record(new TaskReplaced(aggregateId: $this->id->value()));
    }

    public function delete(): void
    {
        $this->status = TaskStatus::DELETED;
        $this->touch();
        $this->record(new TaskDeleted(aggregateId: $this->id->value()));
    }

    /**
     * Still pending work — counts against the parent Project's "has active
     * tasks" check (see ProjectHasActiveTasksException).
     */
    public function isActive(): bool
    {
        return in_array($this->status, [TaskStatus::TODO, TaskStatus::IN_PROGRESS], true);
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

    public function id(): TaskId
    {
        return $this->id;
    }

    public function project(): Project
    {
        return $this->project;
    }

    public function title(): TaskTitle
    {
        return $this->title;
    }

    public function assigneeId(): ?AssigneeId
    {
        return $this->assigneeId;
    }

    public function attachmentId(): ?AttachmentId
    {
        return $this->attachmentId;
    }

    public function status(): TaskStatus
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
