<?php

declare(strict_types=1);

namespace App\Project\Application\Query\GetTasks;

use App\Project\Domain\Entity\Task;
use App\Shared\Domain\Bus\Query\Response;

final class TaskItemResponse implements Response
{
    public readonly string $id;
    public readonly string $title;
    public readonly string $status;
    public readonly ?string $assigneeId;
    public readonly string $createdAt;

    public function __construct(Task $entity)
    {
        $this->id = $entity->id()->value();
        $this->title = $entity->title()->value();
        $this->status = $entity->status()->value;
        $this->assigneeId = $entity->assigneeId()?->value();
        $this->createdAt = $entity->createdAt()->format(\DateTimeInterface::ATOM);
    }
}
