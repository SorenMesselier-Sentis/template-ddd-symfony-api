<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\ErDiagram;

final class ForeignKeyRelationInferrer
{
    /** @var array<string, string> */
    private const array FOREIGN_KEY_TYPE_TO_TABLE = [
        'owner_id' => 'users',
    ];

    /** @var list<string> */
    private const array NATIVE_DOCTRINE_TYPES = [
        'string', 'integer', 'smallint', 'bigint', 'boolean', 'decimal', 'float', 'text',
        'binary', 'blob', 'date', 'date_immutable', 'datetime', 'datetime_immutable',
        'datetimetz', 'datetimetz_immutable', 'time', 'time_immutable', 'dateinterval',
        'array', 'simple_array', 'json', 'object', 'guid', 'uuid',
    ];

    /**
     * @param list<EntityMetadata> $entities
     *
     * @return list<EntityMetadata>
     */
    public function infer(array $entities): array
    {
        $tableByPrimaryKeyType = [];
        $entityFqcnByTable = [];

        foreach ($entities as $entity) {
            $entityFqcnByTable[$entity->tableName] = $entity->entityFqcn;

            foreach ($entity->columns as $column) {
                if ($column->primaryKey && !$this->isNativeDoctrineType($column->type)) {
                    $tableByPrimaryKeyType[$column->type] = $entity->tableName;
                }
            }
        }

        $enrichedEntities = [];

        foreach ($entities as $entity) {
            $relations = $entity->relations;

            foreach ($entity->columns as $column) {
                if ($column->primaryKey) {
                    continue;
                }

                $targetTable = self::FOREIGN_KEY_TYPE_TO_TABLE[$column->type]
                    ?? $tableByPrimaryKeyType[$column->type]
                    ?? null;

                if (null === $targetTable || $targetTable === $entity->tableName) {
                    continue;
                }

                if ($this->isNativeDoctrineType($column->type) && !isset(self::FOREIGN_KEY_TYPE_TO_TABLE[$column->type])) {
                    continue;
                }

                if (!$this->hasRelationToTable($relations, $targetTable, $entityFqcnByTable)) {
                    $relations[] = new RelationMetadata(
                        name: $column->name,
                        cardinality: 'many-to-one',
                        targetEntityFqcn: $entityFqcnByTable[$targetTable] ?? '',
                        targetEntityShortName: $targetTable,
                    );
                }
            }

            $enrichedEntities[] = new EntityMetadata(
                entityFqcn: $entity->entityFqcn,
                tableName: $entity->tableName,
                columns: $entity->columns,
                relations: $relations,
            );
        }

        return $enrichedEntities;
    }

    /**
     * @param list<RelationMetadata> $relations
     * @param array<string, string>  $entityFqcnByTable
     */
    private function hasRelationToTable(array $relations, string $targetTable, array $entityFqcnByTable): bool
    {
        $tableByFqcn = array_flip($entityFqcnByTable);

        foreach ($relations as $relation) {
            $resolvedTargetTable = $tableByFqcn[$relation->targetEntityFqcn] ?? $relation->targetEntityShortName;

            if ($resolvedTargetTable === $targetTable) {
                return true;
            }
        }

        return false;
    }

    private function isNativeDoctrineType(string $type): bool
    {
        return \in_array($type, self::NATIVE_DOCTRINE_TYPES, true);
    }
}
