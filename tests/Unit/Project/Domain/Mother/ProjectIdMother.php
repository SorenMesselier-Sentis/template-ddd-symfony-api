<?php

declare(strict_types=1);

namespace App\Tests\Unit\Project\Domain\Mother;

use App\Project\Domain\ValueObject\ProjectId;

final class ProjectIdMother
{
    public static function random(): ProjectId
    {
        return ProjectId::random();
    }

    public static function create(string $value): ProjectId
    {
        return ProjectId::fromString($value);
    }
}
