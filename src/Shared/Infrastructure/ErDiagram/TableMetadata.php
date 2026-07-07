<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\ErDiagram;

final readonly class TableMetadata
{
    /**
     * @param list<ColumnMetadata>   $columns
     * @param list<RelationMetadata> $relations
     */
    public function __construct(
        public string $tableName,
        public array $columns,
        public array $relations,
    ) {
    }
}
