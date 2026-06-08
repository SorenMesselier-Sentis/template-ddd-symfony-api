<?php

declare(strict_types=1);

namespace App\Document\Domain\ValueObject;

use App\Document\Domain\Exception\InvalidObjectPathException;

final class ObjectPath
{
    private readonly string $value;

    public function __construct(string $value)
    {
        $value = trim($value, '/');
        $this->ensureIsValid($value);
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function forDocument(OwnerId $ownerId, DocumentId $documentId, string $originalName): self
    {
        $sanitized = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($originalName)) ?? 'file';

        if ('' === $sanitized) {
            $sanitized = 'file';
        }

        return new self(sprintf('%s/%s/%s', $ownerId->value(), $documentId->value(), $sanitized));
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    private function ensureIsValid(string $value): void
    {
        if ('' === $value) {
            throw InvalidObjectPathException::withReason('cannot be empty.');
        }

        if (str_contains($value, '\\')) {
            throw InvalidObjectPathException::withReason('must use forward slashes as separators.');
        }
    }
}
