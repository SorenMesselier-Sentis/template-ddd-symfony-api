<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\ErDiagram;

use App\Shared\Infrastructure\ErDiagram\ColumnMetadata;
use App\Shared\Infrastructure\ErDiagram\EntityMetadata;
use App\Shared\Infrastructure\ErDiagram\ForeignKeyRelationInferrer;
use App\Tests\Unit\UnitTestCase;

final class ForeignKeyRelationInferrerTest extends UnitTestCase
{
    private ForeignKeyRelationInferrer $inferrer;

    protected function setUp(): void
    {
        $this->inferrer = new ForeignKeyRelationInferrer();
    }

    public function testItInfersManyToOneRelationsFromForeignKeyColumnTypes(): void
    {
        $entities = [
            new EntityMetadata(
                entityFqcn: 'App\User\Domain\Entity\User',
                tableName: 'users',
                columns: [new ColumnMetadata('id', 'user_id', true)],
                relations: [],
            ),
            new EntityMetadata(
                entityFqcn: 'App\User\Domain\Entity\RefreshToken',
                tableName: 'refresh_tokens',
                columns: [
                    new ColumnMetadata('id', 'refresh_token_id', true),
                    new ColumnMetadata('user_id', 'user_id'),
                ],
                relations: [],
            ),
        ];

        $enriched = $this->inferrer->infer($entities);
        $refreshTokens = $enriched[1];

        $this->assertCount(1, $refreshTokens->relations);
        $this->assertSame('many-to-one', $refreshTokens->relations[0]->cardinality);
        $this->assertSame('users', $refreshTokens->relations[0]->targetEntityShortName);
        $this->assertSame('user_id', $refreshTokens->relations[0]->name);
    }

    public function testItInfersOwnerIdAsRelationToUsers(): void
    {
        $entities = [
            new EntityMetadata(
                entityFqcn: 'App\User\Domain\Entity\User',
                tableName: 'users',
                columns: [new ColumnMetadata('id', 'user_id', true)],
                relations: [],
            ),
            new EntityMetadata(
                entityFqcn: 'App\Document\Domain\Entity\Document',
                tableName: 'documents',
                columns: [
                    new ColumnMetadata('id', 'document_id', true),
                    new ColumnMetadata('owner_id', 'owner_id'),
                ],
                relations: [],
            ),
        ];

        $enriched = $this->inferrer->infer($entities);
        $documents = $enriched[1];

        $this->assertCount(1, $documents->relations);
        $this->assertSame('users', $documents->relations[0]->targetEntityShortName);
    }
}
