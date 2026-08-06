<?php

declare(strict_types=1);

namespace App\Project\Domain\Exception;

use App\Shared\Domain\Exception\InvalidArgumentException;

final class InvalidTaskStatusException extends InvalidArgumentException
{
    public static function withValue(string $value): self
    {
        return new self(sprintf('"%s" is not a valid task status — use "todo", "in_progress" or "done" (deletion goes through DELETE).', $value));
    }

    public function errorCode(): string
    {
        return 'project.invalid_task_status';
    }
}
