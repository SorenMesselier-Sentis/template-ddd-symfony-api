<?php

declare(strict_types=1);

namespace App\Document\Domain\Exception;

use App\Shared\Domain\Exception\NotFoundException;

final class DocumentNotFoundException extends NotFoundException
{
    public static function withId(string $documentId): self
    {
        return new self(sprintf('Document "%s" was not found.', $documentId));
    }

    public function errorCode(): string
    {
        return 'document.not_found';
    }
}
