<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\ErDiagram;

final class ErDiagramValidator
{
    /**
     * @param list<TableMetadata> $tables
     */
    public function assertComplete(string $mermaidBlock, array $tables): void
    {
        foreach ($tables as $table) {
            if (!str_contains($mermaidBlock, $table->tableName.' {')) {
                throw new \RuntimeException(sprintf('Table "%s" is missing from the generated diagram.', $table->tableName));
            }

            $tableBlock = $this->extractTableBlock($mermaidBlock, $table->tableName);

            foreach ($table->columns as $column) {
                $expectedLine = sprintf('%s %s', $this->sanitizeType($column->type), $column->name);

                if (!str_contains($tableBlock, $expectedLine)) {
                    throw new \RuntimeException(sprintf('Column "%s" (%s) of table "%s" is missing from the generated diagram.', $column->name, $column->type, $table->tableName));
                }
            }
        }
    }

    private function extractTableBlock(string $mermaidBlock, string $tableName): string
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
        $normalized = strtolower(trim($type));

        return preg_replace('/[^a-z0-9_]/', '', $normalized) ?? $normalized;
    }
}
