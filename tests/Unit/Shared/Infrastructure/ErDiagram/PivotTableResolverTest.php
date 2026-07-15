<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\ErDiagram;

use App\Shared\Infrastructure\ErDiagram\ColumnMetadata;
use App\Shared\Infrastructure\ErDiagram\ForeignKeyRelationInferrer;
use App\Shared\Infrastructure\ErDiagram\PivotTableResolver;
use App\Shared\Infrastructure\ErDiagram\RelationMetadata;
use App\Shared\Infrastructure\ErDiagram\TableMetadata;
use App\Tests\Unit\UnitTestCase;

final class PivotTableResolverTest extends UnitTestCase
{
    private PivotTableResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new PivotTableResolver();
    }

    public function testItCollapsesPivotTableIntoManyToManyRelation(): void
    {
        $inferrer = new ForeignKeyRelationInferrer();

        $tables = $inferrer->infer([
            new TableMetadata(
                tableName: 'genres',
                columns: [new ColumnMetadata('id', 'UUID', true)],
                relations: [],
            ),
            new TableMetadata(
                tableName: 'artists',
                columns: [new ColumnMetadata('id', 'UUID', true)],
                relations: [],
            ),
            new TableMetadata(
                tableName: 'artist_genres',
                columns: [
                    new ColumnMetadata('artist_id', 'UUID', true),
                    new ColumnMetadata('genre_id', 'UUID', true),
                ],
                relations: [],
            ),
        ]);

        $resolved = $this->resolver->resolve($tables);

        $this->assertCount(2, $resolved);

        $artists = $this->findTableByName($resolved, 'artists');
        $genres = $this->findTableByName($resolved, 'genres');

        $this->assertNull($this->tryFindTableByName($resolved, 'artist_genres'));
        $this->assertCount(1, $artists->relations);
        $this->assertSame('many-to-many', $artists->relations[0]->cardinality);
        $this->assertSame('genres', $artists->relations[0]->targetTable);
        $this->assertSame('artist_genres', $artists->relations[0]->name);
        $this->assertSame([], $genres->relations);
    }

    public function testItKeepsNonPivotTablesUntouched(): void
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
                relations: [
                    new RelationMetadata('user_id', 'many-to-one', 'users'),
                ],
            ),
        ];

        $resolved = $this->resolver->resolve($tables);

        $this->assertCount(2, $resolved);
        $this->assertNotNull($this->tryFindTableByName($resolved, 'refresh_tokens'));
    }

    public function testItParsesPivotTableFromMigrationSql(): void
    {
        $tempDir = sys_get_temp_dir().'/er-diagram-pivot-'.uniqid('', true);
        $migrationDir = $tempDir.'/src/Shared/Infrastructure/Persistence/Migrations';
        mkdir($migrationDir, 0o777, true);

        $migrationFile = $migrationDir.'/Version20990101120000.php';
        file_put_contents($migrationFile, <<<'PHP'
<?php

declare(strict_types=1);

final class Version20990101120000
{
    public function up(): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE genres (
                id UUID NOT NULL,
                PRIMARY KEY (id)
            )
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE artists (
                id UUID NOT NULL,
                PRIMARY KEY (id)
            )
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE artist_genres (
                artist_id UUID NOT NULL,
                genre_id UUID NOT NULL,
                PRIMARY KEY (artist_id, genre_id)
            )
        SQL);
    }
}
PHP);

        $parser = new \App\Shared\Infrastructure\ErDiagram\MigrationParser(
            new ForeignKeyRelationInferrer(),
            new PivotTableResolver(),
        );

        $tables = $parser->parseAll($tempDir, static function (): void {});

        $this->assertCount(2, $tables);
        $this->assertNull($this->tryFindTableByName($tables, 'artist_genres'));

        $artists = $this->findTableByName($tables, 'artists');
        $this->assertCount(1, $artists->relations);
        $this->assertSame('many-to-many', $artists->relations[0]->cardinality);
        $this->assertSame('genres', $artists->relations[0]->targetTable);

        unlink($migrationFile);
        rmdir($migrationDir);
        rmdir(\dirname($migrationDir, 4));
        rmdir(\dirname($migrationDir, 3));
        rmdir(\dirname($migrationDir, 2));
        rmdir(\dirname($migrationDir, 1));
        rmdir($tempDir.'/src/Shared');
        rmdir($tempDir.'/src');
        rmdir($tempDir);
    }

    /**
     * @param list<TableMetadata> $tables
     */
    private function findTableByName(array $tables, string $tableName): TableMetadata
    {
        $table = $this->tryFindTableByName($tables, $tableName);

        if (null === $table) {
            $this->fail(sprintf('Table "%s" not found.', $tableName));
        }

        return $table;
    }

    /**
     * @param list<TableMetadata> $tables
     */
    private function tryFindTableByName(array $tables, string $tableName): ?TableMetadata
    {
        foreach ($tables as $table) {
            if ($table->tableName === $tableName) {
                return $table;
            }
        }

        return null;
    }
}
