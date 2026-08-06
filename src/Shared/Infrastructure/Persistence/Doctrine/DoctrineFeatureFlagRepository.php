<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine;

use App\Shared\Domain\FeatureFlag\FeatureFlag;
use App\Shared\Domain\FeatureFlag\FeatureFlagRepositoryInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;

final class DoctrineFeatureFlagRepository implements FeatureFlagRepositoryInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function findByKey(string $key): ?FeatureFlag
    {
        $row = $this->connection->fetchAssociative(
            'SELECT flag_key, enabled, description, updated_at FROM feature_flags WHERE flag_key = :key',
            ['key' => $key],
        );

        return false !== $row ? $this->hydrate($row) : null;
    }

    public function findAll(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT flag_key, enabled, description, updated_at FROM feature_flags ORDER BY flag_key ASC',
        );

        return array_map($this->hydrate(...), $rows);
    }

    public function save(FeatureFlag $flag): void
    {
        $this->connection->executeStatement(
            <<<'SQL'
                INSERT INTO feature_flags (flag_key, enabled, description, updated_at)
                VALUES (:key, :enabled, :description, :updated_at)
                ON CONFLICT (flag_key) DO UPDATE SET
                    enabled = EXCLUDED.enabled,
                    description = EXCLUDED.description,
                    updated_at = EXCLUDED.updated_at
                SQL,
            [
                'key' => $flag->key,
                'enabled' => $flag->enabled,
                'description' => $flag->description,
                'updated_at' => $flag->updatedAt->format('Y-m-d H:i:s'),
            ],
            [
                'key' => ParameterType::STRING,
                'enabled' => ParameterType::BOOLEAN,
                'description' => ParameterType::STRING,
                'updated_at' => ParameterType::STRING,
            ],
        );
    }

    public function isEnabled(string $key): bool
    {
        $flag = $this->findByKey($key);

        return null !== $flag && $flag->enabled;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): FeatureFlag
    {
        $platform = $this->connection->getDatabasePlatform();
        $enabled = Type::getType(Types::BOOLEAN)->convertToPHPValue($row['enabled'], $platform);

        return new FeatureFlag(
            key: $this->requireString($row, 'flag_key'),
            enabled: (bool) $enabled,
            description: $this->optionalString($row, 'description'),
            updatedAt: new \DateTimeImmutable($this->requireString($row, 'updated_at')),
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function requireString(array $row, string $column): string
    {
        $value = $row[$column] ?? null;

        if (!\is_string($value)) {
            throw new \RuntimeException(sprintf('feature_flags row column "%s" must be a string, %s given.', $column, get_debug_type($value)));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function optionalString(array $row, string $column): ?string
    {
        $value = $row[$column] ?? null;

        if (null === $value) {
            return null;
        }

        if (!\is_string($value)) {
            throw new \RuntimeException(sprintf('feature_flags row column "%s" must be a string or null, %s given.', $column, get_debug_type($value)));
        }

        return $value;
    }
}
