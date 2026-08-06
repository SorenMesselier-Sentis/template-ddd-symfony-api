<?php

declare(strict_types=1);

namespace App\Project\Application\Query\GetTask;

use App\Project\Domain\Entity\Task;
use App\Shared\Domain\Bus\Query\Response;

final class TaskResponse implements Response
{
    public readonly string $id;
    public readonly string $projectId;
    public readonly string $title;
    public readonly string $status;
    public readonly ?string $assigneeId;
    public readonly ?string $attachmentId;
    public readonly string $createdAt;
    public readonly string $updatedAt;

    public function __construct(Task $entity)
    {
        $this->id = $entity->id()->value();
        $this->projectId = $entity->project()->id()->value();
        $this->title = $entity->title()->value();
        $this->status = $entity->status()->value;
        $this->assigneeId = $entity->assigneeId()?->value();
        $this->attachmentId = $entity->attachmentId()?->value();
        $this->createdAt = $entity->createdAt()->format(\DateTimeInterface::ATOM);
        $this->updatedAt = $entity->updatedAt()->format(\DateTimeInterface::ATOM);
    }
}
