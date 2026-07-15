<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\ErDiagram;

final class PivotTableResolver
{
    /**
     * @param list<TableMetadata> $tables
     *
     * @return list<TableMetadata>
     */
    public function resolve(array $tables): array
    {
        /** @var array<string, TableMetadata> $tablesByName */
        $tablesByName = [];

        foreach ($tables as $table) {
            $tablesByName[$table->tableName] = $table;
        }

        /** @var list<array{source: string, target: string, name: string}> $manyToManyPairs */
        $manyToManyPairs = [];

        foreach ($tables as $table) {
            if (!$this->isPivotTable($table)) {
                continue;
            }

            $targets = $this->extractDistinctTargetTables($table);

            if (\count($targets) < 2) {
                continue;
            }

            sort($targets);

            $manyToManyPairs[] = [
                'source' => $targets[0],
                'target' => $targets[1],
                'name' => $table->tableName,
            ];

            unset($tablesByName[$table->tableName]);
        }

        foreach ($manyToManyPairs as $pair) {
            $sourceTable = $tablesByName[$pair['source']] ?? null;

            if (null === $sourceTable) {
                continue;
            }

            $tablesByName[$pair['source']] = $this->attachManyToManyRelation(
                $sourceTable,
                new RelationMetadata(
                    name: $pair['name'],
                    cardinality: 'many-to-many',
                    targetTable: $pair['target'],
                ),
            );
        }

        $resolved = array_values($tablesByName);
        usort(
            $resolved,
            static fn (TableMetadata $left, TableMetadata $right): int => $left->tableName <=> $right->tableName,
        );

        return $resolved;
    }

    private function isPivotTable(TableMetadata $table): bool
    {
        if ($this->hasSurrogatePrimaryKey($table)) {
            return false;
        }

        if (\count($this->foreignKeyColumns($table)) < 2) {
            return false;
        }

        foreach ($table->columns as $column) {
            if ($column->primaryKey || $this->isForeignKeyCandidate($column) || $this->isTimestampColumn($column)) {
                continue;
            }

            return false;
        }

        return \count($this->extractDistinctTargetTables($table)) >= 2;
    }

    private function hasSurrogatePrimaryKey(TableMetadata $table): bool
    {
        foreach ($table->columns as $column) {
            if ($column->primaryKey && 'id' === $column->name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<ColumnMetadata>
     */
    private function foreignKeyColumns(TableMetadata $table): array
    {
        return array_values(array_filter(
            $table->columns,
            fn (ColumnMetadata $column): bool => $this->isForeignKeyCandidate($column),
        ));
    }

    private function isForeignKeyCandidate(ColumnMetadata $column): bool
    {
        return str_contains(strtoupper($column->type), 'UUID')
            && str_ends_with($column->name, '_id');
    }

    private function isTimestampColumn(ColumnMetadata $column): bool
    {
        $type = strtoupper($column->type);

        return str_contains($type, 'TIMESTAMP') || str_contains($type, 'DATETIME');
    }

    /**
     * @return list<string>
     */
    private function extractDistinctTargetTables(TableMetadata $table): array
    {
        $targets = [];

        foreach ($table->relations as $relation) {
            if ('many-to-one' !== $relation->cardinality) {
                continue;
            }

            if (!\in_array($relation->targetTable, $targets, true)) {
                $targets[] = $relation->targetTable;
            }
        }

        return $targets;
    }

    private function attachManyToManyRelation(TableMetadata $table, RelationMetadata $relation): TableMetadata
    {
        foreach ($table->relations as $existingRelation) {
            if (
                $existingRelation->targetTable === $relation->targetTable
                && $existingRelation->cardinality === $relation->cardinality
            ) {
                return $table;
            }
        }

        return new TableMetadata(
            tableName: $table->tableName,
            columns: $table->columns,
            relations: [...$table->relations, $relation],
        );
    }
}
