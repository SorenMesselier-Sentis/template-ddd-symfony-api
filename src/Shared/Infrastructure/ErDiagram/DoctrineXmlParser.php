<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\ErDiagram;

final class DoctrineXmlParser
{
    private const string MAPPING_GLOB = '/src/*/Infrastructure/Persistence/Doctrine/Mapping/*.orm.xml';

    public function __construct(
        private readonly ForeignKeyRelationInferrer $foreignKeyRelationInferrer,
    ) {
    }

    /**
     * @return list<string> Absolute paths to mapping files
     */
    public function discoverMappingFiles(string $projectDir): array
    {
        $pattern = rtrim($projectDir, '/').self::MAPPING_GLOB;
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
     * @return list<EntityMetadata>
     */
    public function parseAll(string $projectDir, callable $writeWarning): array
    {
        $entities = [];

        foreach ($this->discoverMappingFiles($projectDir) as $filePath) {
            $entity = $this->parseFile($filePath, $writeWarning);

            if (null !== $entity) {
                $entities[] = $entity;
            }
        }

        return $this->foreignKeyRelationInferrer->infer($entities);
    }

    /**
     * @param callable(string): void $writeWarning
     */
    public function parseFile(string $filePath, callable $writeWarning): ?EntityMetadata
    {
        $content = @file_get_contents($filePath);

        if (false === $content) {
            $writeWarning(sprintf('Unable to read mapping file: %s', $filePath));

            return null;
        }

        $previous = libxml_use_internal_errors(true);

        try {
            $document = new \DOMDocument();
            $loaded = $document->loadXML($content, LIBXML_NONET);

            if (false === $loaded) {
                $writeWarning(sprintf('Invalid mapping file ignored: %s', $filePath));

                return null;
            }

            $root = $document->documentElement;

            if (null === $root || 'doctrine-mapping' !== $root->localName) {
                $writeWarning(sprintf('Invalid mapping file ignored: %s', $filePath));

                return null;
            }

            return $this->extractEntity($root, $filePath, $writeWarning);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    /**
     * @param callable(string): void $writeWarning
     */
    private function extractEntity(\DOMElement $root, string $filePath, callable $writeWarning): ?EntityMetadata
    {
        $entityNode = null;

        foreach ($root->childNodes as $child) {
            if ($child instanceof \DOMElement && 'entity' === $child->localName) {
                $entityNode = $child;
                break;
            }
        }

        if (null === $entityNode) {
            $writeWarning(sprintf('Mapping file without valid entity ignored: %s', $filePath));

            return null;
        }

        $entityFqcn = trim($entityNode->getAttribute('name'));
        $tableName = trim($entityNode->getAttribute('table'));

        if ('' === $entityFqcn || '' === $tableName) {
            $writeWarning(sprintf('Mapping file without valid entity ignored: %s', $filePath));

            return null;
        }

        $columns = [];
        $relations = [];

        foreach ($entityNode->childNodes as $child) {
            if (!$child instanceof \DOMElement) {
                continue;
            }

            match ($child->localName) {
                'id' => $columns[] = $this->extractColumn($child, true),
                'field' => $columns[] = $this->extractColumn($child),
                'many-to-one', 'one-to-many', 'one-to-one', 'many-to-many' => $relations[] = $this->extractRelation($child),
                default => null,
            };
        }

        return new EntityMetadata(
            entityFqcn: $entityFqcn,
            tableName: $tableName,
            columns: $columns,
            relations: $relations,
        );
    }

    private function extractColumn(\DOMElement $node, bool $primaryKey = false): ColumnMetadata
    {
        $name = trim($node->getAttribute('name'));
        $column = trim($node->getAttribute('column'));
        $type = trim($node->getAttribute('type'));

        if ('' === $type) {
            $type = 'string';
        }

        return new ColumnMetadata(
            name: '' !== $column ? $column : $name,
            type: $type,
            primaryKey: $primaryKey,
        );
    }

    private function extractRelation(\DOMElement $node): RelationMetadata
    {
        $targetEntityFqcn = trim($node->getAttribute('target-entity'));
        $segments = explode('\\', $targetEntityFqcn);
        $shortName = $segments[array_key_last($segments)] ?? $targetEntityFqcn;

        return new RelationMetadata(
            name: '' !== trim($node->getAttribute('field'))
                ? trim($node->getAttribute('field'))
                : trim($node->getAttribute('name')),
            cardinality: $node->localName ?? 'many-to-one',
            targetEntityFqcn: $targetEntityFqcn,
            targetEntityShortName: $shortName,
        );
    }
}
