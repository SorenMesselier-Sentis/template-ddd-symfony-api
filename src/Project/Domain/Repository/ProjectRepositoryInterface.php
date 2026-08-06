<?php

declare(strict_types=1);

namespace App\Project\Domain\Repository;

use App\Project\Domain\Entity\Project;
use App\Project\Domain\ValueObject\OwnerId;
use App\Project\Domain\ValueObject\ProjectId;
use App\Project\Domain\ValueObject\ProjectName;
use App\Shared\Domain\Filter\Filters;

interface ProjectRepositoryInterface
{
    public function save(Project $entity): void;

    public function findById(ProjectId $id): ?Project;

    public function findByIdIncludingDeleted(ProjectId $id): ?Project;

    public function existsByOwnerIdAndName(OwnerId $ownerId, ProjectName $name): bool;

    /** @return list<Project> */
    public function findByOwnerIdAndFilters(OwnerId $ownerId, Filters $filters): array;

    public function countByOwnerIdAndFilters(OwnerId $ownerId, Filters $filters): int;
}
