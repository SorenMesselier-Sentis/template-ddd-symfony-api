<?php

declare(strict_types=1);

namespace App\Shared\Domain\Filter;

use App\Shared\Domain\Exception\InvalidFilterException;

/**
 * Opaque keyset-pagination position: the (createdAt, id) pair of the last row
 * of the previous page. Ordering is always createdAt DESC with id as a
 * tie-breaker, since createdAt alone is not unique (second-level precision).
 */
final class Cursor
{
    public function __construct(
        public readonly \DateTimeImmutable $createdAt,
        public readonly string $id,
    ) {
    }

    public static function decode(string $token): self
    {
        $json = base64_decode($token, true);

        if (false === $json) {
            throw InvalidFilterException::invalidPagination('cursor');
        }

        $data = json_decode($json, true);

        if (!\is_array($data) || !isset($data['created_at'], $data['id']) || !\is_string($data['created_at']) || !\is_string($data['id'])) {
            throw InvalidFilterException::invalidPagination('cursor');
        }

        try {
            $createdAt = new \DateTimeImmutable($data['created_at']);
        } catch (\Exception) {
            throw InvalidFilterException::invalidPagination('cursor');
        }

        return new self($createdAt, $data['id']);
    }

    public function encode(): string
    {
        return base64_encode(json_encode([
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'id' => $this->id,
        ], JSON_THROW_ON_ERROR));
    }
}
