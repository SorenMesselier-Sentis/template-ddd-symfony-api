<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\ErDiagram;

use App\Shared\Infrastructure\ErDiagram\ColumnMetadata;
use App\Shared\Infrastructure\ErDiagram\ForeignKeyRelationInferrer;
use App\Shared\Infrastructure\ErDiagram\TableMetadata;
use App\Tests\Unit\UnitTestCase;

final class ForeignKeyRelationInferrerTest extends UnitTestCase
{
    private ForeignKeyRelationInferrer $inferrer;

    protected function setUp(): void
    {
        $this->inferrer = new ForeignKeyRelationInferrer();
    }

    public function testItInfersManyToOneRelationsFromUuidForeignKeyColumns(): void
    {
        $tables = [
            new TableMetadata(
                tableName: 'users',
                columns: [new ColumnMetadata('id', 'UUID', true)],
                relations: [],
            ),
            new TableMetadata(
                tableName: 'refresh_tokens',
                columns: [
                    new ColumnMetadata('id', 'UUID', true),
                    new ColumnMetadata('user_id', 'UUID'),
                ],
                relations: [],
            ),
        ];

        $enriched = $this->inferrer->infer($tables);
        $refreshTokens = $enriched[1];

        $this->assertCount(1, $refreshTokens->relations);
        $this->assertSame('many-to-one', $refreshTokens->relations[0]->cardinality);
        $this->assertSame('users', $refreshTokens->relations[0]->targetTable);
        $this->assertSame('user_id', $refreshTokens->relations[0]->name);
    }

    public function testItInfersOwnerIdAsRelationToUsers(): void
    {
        $tables = [
            new TableMetadata(
                tableName: 'users',
                columns: [new ColumnMetadata('id', 'UUID', true)],
                relations: [],
            ),
            new TableMetadata(
                tableName: 'documents',
                columns: [
                    new ColumnMetadata('id', 'UUID', true),
                    new ColumnMetadata('owner_id', 'UUID'),
                ],
                relations: [],
            ),
        ];

        $enriched = $this->inferrer->infer($tables);
        $documents = $enriched[1];

        $this->assertCount(1, $documents->relations);
        $this->assertSame('users', $documents->relations[0]->targetTable);
    }
}
