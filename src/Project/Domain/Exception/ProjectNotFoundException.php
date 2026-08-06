<?php

declare(strict_types=1);

namespace App\Project\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;

final class ProjectNotFoundException extends NotFoundException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Project with id "%s" was not found.', $id));
    }

    public function errorCode(): string
    {
        return 'project.not_found';
    }
}
