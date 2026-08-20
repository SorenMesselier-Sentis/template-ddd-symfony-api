<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\ErDiagram;

final class ForeignKeyRelationInferrer
{
    /** @var array<string, string> */
    private const array FOREIGN_KEY_COLUMN_TO_TABLE = [
        'owner_id' => 'users',
        'assignee_id' => 'users',
        'attachment_id' => 'documents',
    ];

    /**
     * Columns that look like foreign keys (UUID, `_id` suffix) but intentionally
     * reference no single table, so they must not trigger an unresolved-FK warning.
     *
     * @var list<string>
     */
    private const array IGNORED_COLUMNS = [
        'aggregate_id', // outbox_messages: polymorphic pointer to any aggregate root
    ];

    /**
     * @param list<TableMetadata>         $tables
     * @param callable(string): void|null $writeWarning
     *
     * @return list<TableMetadata>
     */
    public function infer(array $tables, ?callable $writeWarning = null): array
    {
        $writeWarning ??= static function (string $message): void {
        };
        $knownTables = array_map(static fn (TableMetadata $table): string => $table->tableName, $tables);
        $enrichedTables = [];

        foreach ($tables as $table) {
            $relations = $table->relations;

            foreach ($table->columns as $column) {
                if (!$this->isForeignKeyCandidate($column) || \in_array($column->name, self::IGNORED_COLUMNS, true)) {
                    continue;
                }

                if ($this->isSurrogatePrimaryKeyColumn($table, $column)) {
                    continue;
                }

                $targetTable = self::FOREIGN_KEY_COLUMN_TO_TABLE[$column->name]
                    ?? $this->resolveTargetTableFromColumnName($column->name, $knownTables);

                if ($targetTable === $table->tableName) {
                    continue;
                }

                if (null === $targetTable) {
                    $writeWarning(sprintf(
                        'Column "%s.%s" looks like a foreign key but no matching table was found. '
                        .'If it is a cross-BC UUID reference, add it to ForeignKeyRelationInferrer::FOREIGN_KEY_COLUMN_TO_TABLE '
                        .'(or to IGNORED_COLUMNS if it is intentionally polymorphic).',
                        $table->tableName,
                        $column->name,
                    ));

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
