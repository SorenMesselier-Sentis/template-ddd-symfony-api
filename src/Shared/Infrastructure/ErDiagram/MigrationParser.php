<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\ErDiagram;

final class MigrationParser
{
    private const string MIGRATIONS_GLOB = '/src/Shared/Infrastructure/Persistence/Migrations/Version*.php';

    public function __construct(
        private readonly ForeignKeyRelationInferrer $foreignKeyRelationInferrer,
    ) {
    }

    /**
     * @return list<string>
     */
    public function discoverMigrationFiles(string $projectDir): array
    {
        $pattern = rtrim($projectDir, '/').self::MIGRATIONS_GLOB;
        $files = glob($pattern);

        if (false === $files) {
            return [];
        }

        sort($files);

        return $files;
    }

    /**
     * @param callable(string): void $writeWarning
     *
     * @return list<TableMetadata>
     */
    public function parseAll(string $projectDir, callable $writeWarning): array
    {
        /** @var array<string, TableMetadata> $tables */
        $tables = [];

        foreach ($this->discoverMigrationFiles($projectDir) as $filePath) {
            $this->parseFile($filePath, $tables, $writeWarning);
        }

        if ([] === $tables) {
            return [];
        }

        return $this->foreignKeyRelationInferrer->infer(array_values($tables));
    }

    /**
     * @param array<string, TableMetadata> $tables
     * @param callable(string): void       $writeWarning
     */
    private function parseFile(string $filePath, array &$tables, callable $writeWarning): void
    {
        $content = @file_get_contents($filePath);

        if (false === $content) {
            $writeWarning(sprintf('Unable to read migration file: %s', $filePath));

            return;
        }

        foreach ($this->extractSqlStatements($content) as $sql) {
            $createTable = $this->parseCreateTable($sql);

            if (null !== $createTable) {
                $tables[$createTable->tableName] = $createTable;

                continue;
            }

            $foreignKeys = $this->parseForeignKeysFromAlterTable($sql);

            foreach ($foreignKeys as $foreignKey) {
                $this->appendRelation($tables, $foreignKey['sourceTable'], $foreignKey);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function extractSqlStatements(string $content): array
    {
        $statements = [];

        if (preg_match_all("/addSql\s*\(\s*<<<'SQL'\s*(.*?)SQL/s", $content, $matches) > 0) {
            foreach ($matches[1] as $sql) {
                $statements[] = trim($sql);
            }
        }

        if (preg_match_all("/addSql\s*\(\s*'((?:[^'\\\\]|\\\\.)*)'\s*\)/s", $content, $matches) > 0) {
            foreach ($matches[1] as $sql) {
                $statements[] = trim(stripcslashes($sql));
            }
        }

        return $statements;
    }

    private function parseCreateTable(string $sql): ?TableMetadata
    {
        if (1 !== preg_match('/CREATE\s+TABLE\s+(\w+)\s*\((.*)\)\s*$/is', $sql, $matches)) {
            return null;
        }

        $tableName = strtolower($matches[1]);
        $body = $matches[2];
        $columns = [];
        $relations = [];
        $primaryKeyColumns = [];

        if (1 === preg_match('/PRIMARY\s+KEY\s*\(([^)]+)\)/i', $body, $primaryKeyMatch)) {
            $primaryKeyColumns = array_map(static fn (string $value): string => trim($value), explode(',', $primaryKeyMatch[1]));
        }

        foreach ($this->splitTableBodyLines($body) as $line) {
            if ($this->isTableLevelConstraintLine($line)) {
                $relations = [...$relations, ...$this->parseForeignKeysFromLine($tableName, $line)];

                continue;
            }

            $column = $this->parseColumnDefinition($line);

            if (null === $column) {
                continue;
            }

            if (\in_array($column->name, $primaryKeyColumns, true)) {
                $column = new ColumnMetadata($column->name, $column->type, true);
            }

            $columns[] = $column;
        }

        if ([] === $columns) {
            return null;
        }

        return new TableMetadata($tableName, $columns, $relations);
    }

    /**
     * @return list<string>
     */
    private function splitTableBodyLines(string $body): array
    {
        $lines = [];

        $rawLines = preg_split('/\r?\n/', $body);

        foreach (false === $rawLines ? [] : $rawLines as $line) {
            $line = trim($line, " \t\n\r,");

            if ('' !== $line) {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    private function isTableLevelConstraintLine(string $line): bool
    {
        $upper = strtoupper($line);

        return str_starts_with($upper, 'PRIMARY KEY')
            || str_starts_with($upper, 'CONSTRAINT')
            || str_starts_with($upper, 'FOREIGN KEY')
            || str_starts_with($upper, 'UNIQUE');
    }

    private function parseColumnDefinition(string $line): ?ColumnMetadata
    {
        if (1 !== preg_match('/^"?(\w+)"?\s+(.+)$/i', $line, $matches)) {
            return null;
        }

        $name = $matches[1];
        $type = $this->extractSqlType($matches[2]);

        if ('' === $type) {
            return null;
        }

        return new ColumnMetadata(
            name: $name,
            type: $type,
            primaryKey: false,
        );
    }

    private function extractSqlType(string $definition): string
    {
        $withoutDefault = preg_replace('/\s+DEFAULT\b.*/i', '', $definition) ?? $definition;
        $withoutNullability = preg_replace('/\s+(NOT\s+NULL|NULL)\b.*/i', '', $withoutDefault) ?? $withoutDefault;

        return trim($withoutNullability);
    }

    /**
     * @return list<array{name: string, cardinality: string, sourceTable: string, targetTable: string}>
     */
    private function parseForeignKeysFromAlterTable(string $sql): array
    {
        if (1 !== preg_match('/ALTER\s+TABLE\s+(\w+)\s+ADD\s+CONSTRAINT\s+\w+\s+FOREIGN\s+KEY\s*\(([^)]+)\)\s+REFERENCES\s+(\w+)\s*\(([^)]+)\)/i', $sql, $matches)) {
            return [];
        }

        return [[
            'name' => trim(explode(',', $matches[2])[0]),
            'cardinality' => 'many-to-one',
            'sourceTable' => strtolower($matches[1]),
            'targetTable' => strtolower($matches[3]),
        ]];
    }

    /**
     * @return list<RelationMetadata>
     */
    private function parseForeignKeysFromLine(string $sourceTable, string $line): array
    {
        if (1 !== preg_match('/FOREIGN\s+KEY\s*\(([^)]+)\)\s+REFERENCES\s+(\w+)\s*\(([^)]+)\)/i', $line, $matches)) {
            return [];
        }

        $columnName = trim(explode(',', $matches[1])[0]);

        return [new RelationMetadata(
            name: $columnName,
            cardinality: 'many-to-one',
            targetTable: strtolower($matches[2]),
        )];
    }

    /**
     * @param array<string, TableMetadata>                                                       $tables
     * @param array{name: string, cardinality: string, sourceTable: string, targetTable: string} $foreignKey
     */
    private function appendRelation(array &$tables, string $sourceTable, array $foreignKey): void
    {
        if (!isset($tables[$sourceTable])) {
            return;
        }

        $table = $tables[$sourceTable];
        $relations = $table->relations;

        foreach ($relations as $relation) {
            if ($relation->targetTable === $foreignKey['targetTable'] && $relation->name === $foreignKey['name']) {
                return;
            }
        }

        $relations[] = new RelationMetadata(
            name: $foreignKey['name'],
            cardinality: $foreignKey['cardinality'],
            targetTable: $foreignKey['targetTable'],
        );

        $tables[$sourceTable] = new TableMetadata($table->tableName, $table->columns, $relations);
    }
}
