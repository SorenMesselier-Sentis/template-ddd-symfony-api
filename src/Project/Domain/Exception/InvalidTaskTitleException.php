<?php

declare(strict_types=1);

namespace App\Project\Domain\Exception;

use App\Shared\Domain\Exception\InvalidArgumentException;

final class InvalidTaskTitleException extends InvalidArgumentException
{
    public static function withReason(string $reason): self
    {
        return new self(sprintf('Invalid task title: %s', $reason));
    }

    public function errorCode(): string
    {
        return 'project.invalid_task_title';
    }
}
