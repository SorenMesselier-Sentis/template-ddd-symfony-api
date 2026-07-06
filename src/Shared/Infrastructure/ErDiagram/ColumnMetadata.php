<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\ErDiagram;

final readonly class ColumnMetadata
{
    public function __construct(
        public string $name,
        public string $type,
    ) {
    }
}
