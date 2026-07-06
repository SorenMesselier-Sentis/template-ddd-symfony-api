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
     * @param list<EntityMetadata> $entities
     */
    public function render(array $entities): string
    {
        $lines = ['```mermaid', 'erDiagram'];

        foreach ($this->sortEntities($entities) as $entity) {
            $lines[] = sprintf('    %s {', $entity->tableName);

            foreach ($entity->columns as $column) {
                $lines[] = sprintf('        %s %s', $this->sanitizeType($column->type), $column->name);
            }

            $lines[] = '    }';
        }

        foreach ($this->deduplicateRelations($entities) as $relationLine) {
            $lines[] = $relationLine;
        }

        $lines[] = '```';

        return implode("\n", $lines)."\n";
    }

    /**
     * @param list<EntityMetadata> $entities
     *
     * @return list<EntityMetadata>
     */
    private function sortEntities(array $entities): array
    {
        usort(
            $entities,
            static fn (EntityMetadata $left, EntityMetadata $right): int => $left->tableName <=> $right->tableName,
        );

        return $entities;
    }

    /**
     * @param list<EntityMetadata> $entities
     *
     * @return list<string>
     */
    private function deduplicateRelations(array $entities): array
    {
        $entityTableByFqcn = [];

        foreach ($entities as $entity) {
            $entityTableByFqcn[$entity->entityFqcn] = $entity->tableName;
        }

        $seen = [];
        $lines = [];

        foreach ($entities as $entity) {
            foreach ($entity->relations as $relation) {
                $targetTable = $entityTableByFqcn[$relation->targetEntityFqcn] ?? $relation->targetEntityShortName;
                $symbol = self::RELATION_SYMBOLS[$relation->cardinality] ?? '}o--||';
                $dedupKey = $this->relationDedupKey($entity->tableName, $targetTable, $relation->cardinality);

                if (isset($seen[$dedupKey])) {
                    continue;
                }

                $seen[$dedupKey] = true;
                $label = '' !== $relation->name ? '"'.$relation->name.'"' : '""';

                $lines[] = sprintf(
                    '    %s %s %s : %s',
                    $entity->tableName,
                    $symbol,
                    $targetTable,
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
        return preg_replace('/[^a-zA-Z0-9_]/', '', $type) ?? $type;
    }
}
