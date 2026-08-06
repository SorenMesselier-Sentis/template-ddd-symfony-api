<?php

declare(strict_types=1);

namespace App\Project\Domain\Exception;

use App\Shared\Domain\Exception\AlreadyExistsException;

final class ProjectAlreadyExistsException extends AlreadyExistsException
{
    public static function withField(string $field, string $value): self
    {
        return new self(sprintf('Project with %s "%s" already exists.', $field, $value));
    }

    public function errorCode(): string
    {
        return 'project.already_exists';
    }
}
