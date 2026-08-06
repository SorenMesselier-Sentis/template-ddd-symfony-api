<?php

declare(strict_types=1);

namespace App\Project\Application\Query\GetProjects;

use App\Project\Domain\Entity\Project;
use App\Shared\Domain\Bus\Query\Response;

final class ProjectItemResponse implements Response
{
    public readonly string $id;
    public readonly string $name;
    public readonly string $status;
    public readonly string $createdAt;

    public function __construct(Project $entity)
    {
        $this->id = $entity->id()->value();
        $this->name = $entity->name()->value();
        $this->status = $entity->status()->value;
        $this->createdAt = $entity->createdAt()->format(\DateTimeInterface::ATOM);
    }
}
