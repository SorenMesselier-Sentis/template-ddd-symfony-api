<?php

declare(strict_types=1);

namespace App\Project\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class ProjectHasActiveTasksException extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Project "%s" still has active tasks and cannot be deleted.', $id));
    }

    public function errorCode(): string
    {
        return 'project.has_active_tasks';
    }
}
