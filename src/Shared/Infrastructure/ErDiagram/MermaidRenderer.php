<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\ErDiagram;

final class MermaidRenderer
{
    private const array RELATION_SYMBOLS = [
        'many-to-one' => '}o--||',
        'one-to-many' => '||--o{',
        'one-to-one' => '||--||',
        'many-to-many' => '}o--o{',
    ];

    /**
     * @param list<TableMetadata> $tables
     */
    public function render(array $tables): string
    {
        $lines = ['```mermaid', 'erDiagram'];

        foreach ($this->sortTables($tables) as $table) {
            $lines[] = sprintf('    %s {', $table->tableName);

            foreach ($table->columns as $column) {
                $lines[] = sprintf('        %s %s', $this->sanitizeType($column->type), $column->name);
            }

            $lines[] = '    }';
        }

        foreach ($this->deduplicateRelations($tables) as $relationLine) {
            $lines[] = $relationLine;
        }

        $lines[] = '```';

        return implode("\n", $lines)."\n";
    }

    /**
     * @param list<TableMetadata> $tables
     *
     * @return list<TableMetadata>
     */
    private function sortTables(array $tables): array
    {
        usort(
            $tables,
            static fn (TableMetadata $left, TableMetadata $right): int => $left->tableName <=> $right->tableName,
        );

        return $tables;
    }

    /**
     * @param list<TableMetadata> $tables
     *
     * @return list<string>
     */
    private function deduplicateRelations(array $tables): array
    {
        $seen = [];
        $lines = [];

        foreach ($tables as $table) {
            foreach ($table->relations as $relation) {
                $symbol = self::RELATION_SYMBOLS[$relation->cardinality] ?? '}o--||';
                $dedupKey = $this->relationDedupKey($table->tableName, $relation->targetTable, $relation->cardinality);

                if (isset($seen[$dedupKey])) {
                    continue;
                }

                $seen[$dedupKey] = true;
                $label = '' !== $relation->name ? '"'.$relation->name.'"' : '""';

                $lines[] = sprintf(
                    '    %s %s %s : %s',
                    $table->tableName,
                    $symbol,
                    $relation->targetTable,
                    $label,
                );
            }
        }

        sort($lines);

        return $lines;
    }

    private function relationDedupKey(string $sourceTable, string $targetTable, string $cardinality): string
    {
        $tables = [$sourceTable, $targetTable];
        sort($tables);

        return implode('|', $tables).'|'.$cardinality;
    }

    private function sanitizeType(string $type): string
    {
        $normalized = strtolower(trim($type));

        return preg_replace('/[^a-z0-9_]/', '', $normalized) ?? $normalized;
    }
}
