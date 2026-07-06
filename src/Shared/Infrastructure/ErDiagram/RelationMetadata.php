<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\ErDiagram;

final readonly class RelationMetadata
{
    public function __construct(
        public string $name,
        public string $cardinality,
        public string $targetEntityFqcn,
        public string $targetEntityShortName,
    ) {
    }
}
