<?php

declare(strict_types=1);

namespace App\Tests\Unit\Project\Domain\Mother;

use App\Project\Domain\Entity\Project;
use App\Project\Domain\Entity\Task;
use App\Project\Domain\ValueObject\AssigneeId;
use App\Project\Domain\ValueObject\AttachmentId;
use App\Project\Domain\ValueObject\TaskId;
use App\Project\Domain\ValueObject\TaskTitle;

final class TaskMother
{
    public static function create(
        ?TaskId $id = null,
        ?Project $project = null,
        ?TaskTitle $title = null,
        ?AssigneeId $assigneeId = null,
        ?AttachmentId $attachmentId = null,
    ): Task {
        return Task::create(
            id: $id ?? TaskIdMother::random(),
            project: $project ?? ProjectMother::create(),
            title: $title ?? TaskTitle::fromString('Design the homepage mockup'),
            assigneeId: $assigneeId,
            attachmentId: $attachmentId,
        );
    }
}
