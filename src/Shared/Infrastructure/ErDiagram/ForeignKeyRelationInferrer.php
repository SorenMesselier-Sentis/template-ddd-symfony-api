<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\ErDiagram;

final class ForeignKeyRelationInferrer
{
    /** @var array<string, string> */
    private const array FOREIGN_KEY_COLUMN_TO_TABLE = [
        'owner_id' => 'users',
    ];

    /**
     * @param list<TableMetadata> $tables
     *
     * @return list<TableMetadata>
     */
    public function infer(array $tables): array
    {
        $knownTables = array_map(static fn (TableMetadata $table): string => $table->tableName, $tables);
        $enrichedTables = [];

        foreach ($tables as $table) {
            $relations = $table->relations;

            foreach ($table->columns as $column) {
                if (!$this->isForeignKeyCandidate($column)) {
                    continue;
                }

                if ($this->isSurrogatePrimaryKeyColumn($table, $column)) {
                    continue;
                }

                $targetTable = self::FOREIGN_KEY_COLUMN_TO_TABLE[$column->name]
                    ?? $this->resolveTargetTableFromColumnName($column->name, $knownTables);

                if (null === $targetTable || $targetTable === $table->tableName) {
                    continue;
                }

                if (!$this->hasRelationToTable($relations, $targetTable)) {
                    $relations[] = new RelationMetadata(
                        name: $column->name,
                        cardinality: 'many-to-one',
                        targetTable: $targetTable,
                    );
                }
            }

            $enrichedTables[] = new TableMetadata(
                tableName: $table->tableName,
                columns: $table->columns,
                relations: $relations,
            );
        }

        return $enrichedTables;
    }

    private function isForeignKeyCandidate(ColumnMetadata $column): bool
    {
        return str_contains(strtoupper($column->type), 'UUID')
            && str_ends_with($column->name, '_id');
    }

    private function isSurrogatePrimaryKeyColumn(TableMetadata $table, ColumnMetadata $column): bool
    {
        if (!$column->primaryKey || 'id' !== $column->name) {
            return false;
        }

        $primaryKeyColumns = array_filter(
            $table->columns,
            static fn (ColumnMetadata $currentColumn): bool => $currentColumn->primaryKey,
        );

        return 1 === \count($primaryKeyColumns);
    }

    /**
     * @param list<string> $knownTables
     */
    private function resolveTargetTableFromColumnName(string $columnName, array $knownTables): ?string
    {
        if (!str_ends_with($columnName, '_id')) {
            return null;
        }

        $baseName = substr($columnName, 0, -3);
        $candidates = [$baseName.'s', $baseName];

        foreach ($candidates as $candidate) {
            if (\in_array($candidate, $knownTables, true)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param list<RelationMetadata> $relations
     */
    private function hasRelationToTable(array $relations, string $targetTable): bool
    {
        foreach ($relations as $relation) {
            if ($relation->targetTable === $targetTable) {
                return true;
            }
        }

        return false;
    }
}
