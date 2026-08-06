<?php

declare(strict_types=1);

namespace App\Project\Application\Query\GetProject;

use App\Project\Domain\Entity\Project;
use App\Shared\Domain\Bus\Query\Response;

final class ProjectResponse implements Response
{
    public readonly string $id;
    public readonly string $ownerId;
    public readonly string $name;
    public readonly string $status;
    public readonly string $createdAt;
    public readonly string $updatedAt;

    public function __construct(Project $entity)
    {
        $this->id = $entity->id()->value();
        $this->ownerId = $entity->ownerId()->value();
        $this->name = $entity->name()->value();
        $this->status = $entity->status()->value;
        $this->createdAt = $entity->createdAt()->format(\DateTimeInterface::ATOM);
        $this->updatedAt = $entity->updatedAt()->format(\DateTimeInterface::ATOM);
    }
}
