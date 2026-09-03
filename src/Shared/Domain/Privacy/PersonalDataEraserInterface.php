<?php

declare(strict_types=1);

namespace App\Shared\Domain\Privacy;

interface PersonalDataEraserInterface
{
    public function key(): string;

    public function erase(string $subjectId): void;
}
