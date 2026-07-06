<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\ErDiagram;

final class ErDiagramValidator
{
    /**
     * @param list<EntityMetadata> $entities
     */
    public function assertComplete(string $mermaidBlock, array $entities): void
    {
        foreach ($entities as $entity) {
            if (!str_contains($mermaidBlock, $entity->tableName.' {')) {
                throw new \RuntimeException(sprintf('Table "%s" is missing from the generated diagram.', $entity->tableName));
            }

            $entityBlock = $this->extractEntityBlock($mermaidBlock, $entity->tableName);

            foreach ($entity->columns as $column) {
                $expectedLine = sprintf('%s %s', $this->sanitizeType($column->type), $column->name);

                if (!str_contains($entityBlock, $expectedLine)) {
                    throw new \RuntimeException(sprintf('Column "%s" (%s) of table "%s" is missing from the generated diagram.', $column->name, $column->type, $entity->tableName));
                }
            }
        }
    }

    private function extractEntityBlock(string $mermaidBlock, string $tableName): string
    {
        $pattern = sprintf('/%s\s*\{([^}]*)\}/s', preg_quote($tableName, '/'));
        $matches = [];

        if (1 !== preg_match($pattern, $mermaidBlock, $matches)) {
            return '';
        }

        return $matches[1];
    }

    private function sanitizeType(string $type): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $type) ?? $type;
    }
}
