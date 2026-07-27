<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messaging\Outbox;

/**
 * Typed view of an unpublished outbox_messages row.
 */
final readonly class OutboxMessageRow
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public string $id,
        public string $eventClass,
        public string $aggregateId,
        public array $payload,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            id: self::requireString($row, 'id'),
            eventClass: self::requireString($row, 'event_class'),
            aggregateId: self::optionalString($row, 'aggregate_id') ?? '',
            payload: self::decodePayload($row['payload'] ?? null),
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function requireString(array $row, string $column): string
    {
        $value = $row[$column] ?? null;

        if (!\is_string($value) || '' === $value) {
            throw new \RuntimeException(sprintf('Outbox row column "%s" must be a non-empty string, %s given.', $column, \get_debug_type($value)));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function optionalString(array $row, string $column): ?string
    {
        if (!\array_key_exists($column, $row) || null === $row[$column]) {
            return null;
        }

        $value = $row[$column];

        if (!\is_string($value)) {
            throw new \RuntimeException(sprintf('Outbox row column "%s" must be a string or null, %s given.', $column, \get_debug_type($value)));
        }

        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    private static function decodePayload(mixed $payload): array
    {
        if (\is_string($payload)) {
            try {
                $decoded = json_decode($payload, true, 512, \JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new \RuntimeException('Outbox row payload is not valid JSON.', 0, $e);
            }
        } elseif (\is_array($payload)) {
            $decoded = $payload;
        } else {
            throw new \RuntimeException(sprintf('Outbox row payload must be a JSON string or array, %s given.', \get_debug_type($payload)));
        }

        if (!\is_array($decoded)) {
            throw new \RuntimeException('Outbox row payload must decode to an object.');
        }

        $normalized = [];
        foreach ($decoded as $key => $value) {
            if (!\is_string($key)) {
                throw new \RuntimeException('Outbox row payload keys must be strings.');
            }
            $normalized[$key] = $value;
        }

        return $normalized;
    }
}
