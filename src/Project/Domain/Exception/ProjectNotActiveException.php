<?php

declare(strict_types=1);

namespace App\Project\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class ProjectNotActiveException extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Project "%s" is not active — archived or deleted projects cannot receive new tasks.', $id));
    }

    public function errorCode(): string
    {
        return 'project.not_active';
    }
}
