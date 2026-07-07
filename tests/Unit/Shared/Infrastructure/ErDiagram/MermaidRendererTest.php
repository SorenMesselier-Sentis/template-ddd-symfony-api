<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\ErDiagram;

use App\Shared\Infrastructure\ErDiagram\ColumnMetadata;
use App\Shared\Infrastructure\ErDiagram\MermaidRenderer;
use App\Shared\Infrastructure\ErDiagram\RelationMetadata;
use App\Shared\Infrastructure\ErDiagram\TableMetadata;
use App\Tests\Unit\UnitTestCase;

final class MermaidRendererTest extends UnitTestCase
{
    private MermaidRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = new MermaidRenderer();
    }

    public function testItRendersErDiagramBlock(): void
    {
        $tables = [
            new TableMetadata(
                tableName: 'users',
                columns: [
                    new ColumnMetadata('id', 'UUID', true),
                    new ColumnMetadata('first_name', 'VARCHAR(100)'),
                ],
                relations: [],
            ),
        ];

        $output = $this->renderer->render($tables);

        $this->assertStringStartsWith("```mermaid\nerDiagram\n", $output);
        $this->assertStringEndsWith("```\n", $output);
        $this->assertStringContainsString("users {\n        uuid id\n        varchar100 first_name\n    }", $output);
    }

    public function testItRendersRelationsWithCardinalitySymbols(): void
    {
        $tables = [
            new TableMetadata(
                tableName: 'orders',
                columns: [new ColumnMetadata('id', 'UUID', true)],
                relations: [
                    new RelationMetadata('customer_id', 'many-to-one', 'users'),
                ],
            ),
            new TableMetadata(
                tableName: 'users',
                columns: [new ColumnMetadata('id', 'UUID', true)],
                relations: [],
            ),
        ];

        $output = $this->renderer->render($tables);

        $this->assertSame(1, substr_count($output, '}o--||'));
        $this->assertStringContainsString('orders }o--|| users : "customer_id"', $output);
    }
}
