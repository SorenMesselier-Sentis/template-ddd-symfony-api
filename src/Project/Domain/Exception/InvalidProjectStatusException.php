<?php

declare(strict_types=1);

namespace App\Project\Domain\Exception;

use App\Shared\Domain\Exception\InvalidArgumentException;

final class InvalidProjectStatusException extends InvalidArgumentException
{
    public static function withValue(string $value): self
    {
        return new self(sprintf('"%s" is not a valid project status — use "active" or "archived" (deletion goes through DELETE).', $value));
    }

    public function errorCode(): string
    {
        return 'project.invalid_status';
    }
}
