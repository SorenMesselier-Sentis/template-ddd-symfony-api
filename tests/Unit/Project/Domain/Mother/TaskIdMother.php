<?php

declare(strict_types=1);

namespace App\Tests\Unit\Project\Domain\Mother;

use App\Project\Domain\ValueObject\TaskId;

final class TaskIdMother
{
    public static function random(): TaskId
    {
        return TaskId::random();
    }

    public static function create(string $value): TaskId
    {
        return TaskId::fromString($value);
    }
}
