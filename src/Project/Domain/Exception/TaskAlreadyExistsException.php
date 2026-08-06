<?php

declare(strict_types=1);

namespace App\Project\Domain\Exception;

use App\Shared\Domain\Exception\AlreadyExistsException;

final class TaskAlreadyExistsException extends AlreadyExistsException
{
    public static function withField(string $field, string $value): self
    {
        return new self(sprintf('Task with %s "%s" already exists.', $field, $value));
    }

    public function errorCode(): string
    {
        return 'task.already_exists';
    }
}
