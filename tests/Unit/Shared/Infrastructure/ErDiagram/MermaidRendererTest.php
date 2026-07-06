<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\ErDiagram;

use App\Shared\Infrastructure\ErDiagram\ColumnMetadata;
use App\Shared\Infrastructure\ErDiagram\EntityMetadata;
use App\Shared\Infrastructure\ErDiagram\MermaidRenderer;
use App\Shared\Infrastructure\ErDiagram\RelationMetadata;
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
        $entities = [
            new EntityMetadata(
                entityFqcn: 'App\User\Domain\Entity\User',
                tableName: 'users',
                columns: [
                    new ColumnMetadata('id', 'user_id'),
                    new ColumnMetadata('email', 'email'),
                ],
                relations: [],
            ),
        ];

        $output = $this->renderer->render($entities);

        $this->assertStringStartsWith("```mermaid\nerDiagram\n", $output);
        $this->assertStringEndsWith("```\n", $output);
        $this->assertStringContainsString("users {\n        user_id id\n        email email\n    }", $output);
    }

    public function testItRendersRelationsWithCardinalitySymbols(): void
    {
        $entities = [
            new EntityMetadata(
                entityFqcn: 'App\Order\Domain\Entity\Order',
                tableName: 'orders',
                columns: [new ColumnMetadata('id', 'integer')],
                relations: [
                    new RelationMetadata('customer', 'many-to-one', 'App\User\Domain\Entity\User', 'User'),
                ],
            ),
            new EntityMetadata(
                entityFqcn: 'App\User\Domain\Entity\User',
                tableName: 'users',
                columns: [new ColumnMetadata('id', 'integer')],
                relations: [],
            ),
        ];

        $output = $this->renderer->render($entities);

        $this->assertSame(1, substr_count($output, '}o--||'));
        $this->assertStringContainsString('orders }o--|| users : "customer"', $output);
    }
}
