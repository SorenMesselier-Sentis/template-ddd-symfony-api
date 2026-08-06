<?php

declare(strict_types=1);

namespace App\Tests\Unit\Project\Domain\Mother;

use App\Project\Domain\Entity\Project;
use App\Project\Domain\ValueObject\OwnerId;
use App\Project\Domain\ValueObject\ProjectId;
use App\Project\Domain\ValueObject\ProjectName;

final class ProjectMother
{
    public static function create(
        ?ProjectId $id = null,
        ?OwnerId $ownerId = null,
        ?ProjectName $name = null,
    ): Project {
        return Project::create(
            id: $id ?? ProjectIdMother::random(),
            ownerId: $ownerId ?? OwnerId::random(),
            name: $name ?? ProjectName::fromString('Website Redesign'),
        );
    }
}
