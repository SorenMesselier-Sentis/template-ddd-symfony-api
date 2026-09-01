<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\ErDiagram;

use App\Shared\Infrastructure\ErDiagram\ForeignKeyRelationInferrer;
use App\Shared\Infrastructure\ErDiagram\MigrationParser;
use App\Shared\Infrastructure\ErDiagram\PivotTableResolver;
use App\Tests\Unit\UnitTestCase;

final class MigrationParserTest extends UnitTestCase
{
    private MigrationParser $parser;
    private string $projectDir;

    protected function setUp(): void
    {
        $this->parser = new MigrationParser(new ForeignKeyRelationInferrer(), new PivotTableResolver());
        $this->projectDir = \dirname(__DIR__, 5);
    }

    public function testItDiscoversAllProjectMigrationFiles(): void
    {
        $files = $this->parser->discoverMigrationFiles($this->projectDir);

        $this->assertCount(12, $files);
        $this->assertStringEndsWith('Version20260101000001.php', $files[0]);
        $this->assertStringEndsWith('Version20260901124906.php', $files[11]);
    }

    public function testItParsesUsersTableWithSqlColumnTypes(): void
    {
        $warnings = [];
        $tables = $this->parser->parseAll($this->projectDir, static function (string $message) use (&$warnings): void {
            $warnings[] = $message;
        });

        $this->assertSame([], $warnings);

        $users = $this->findTableByName($tables, 'users');
        $this->assertSame('VARCHAR(100)', $this->findColumn($users, 'first_name')->type);
        $this->assertSame('VARCHAR(254)', $this->findColumn($users, 'email')->type);
        $this->assertSame('JSON', $this->findColumn($users, 'roles')->type);
        $this->assertSame('TIMESTAMP(0) WITHOUT TIME ZONE', $this->findColumn($users, 'email_verified_at')->type);
        $this->assertSame('TIMESTAMP(0) WITHOUT TIME ZONE', $this->findColumn($users, 'created_at')->type);
        $this->assertTrue($this->findColumn($users, 'id')->primaryKey);
    }

    public function testItReturnsNoTablesWhenMigrationContainsNoCreateTable(): void
    {
        $tempDir = sys_get_temp_dir().'/er-diagram-migration-'.uniqid('', true);
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
    }
}
PHP);

        $warnings = [];
        $tables = $this->parser->parseAll($tempDir, static function (string $message) use (&$warnings): void {
            $warnings[] = $message;
        });

        $this->assertSame([], $tables);
        $this->assertSame([], $warnings);

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
     * @param list<\App\Shared\Infrastructure\ErDiagram\TableMetadata> $tables
     */
    private function findTableByName(array $tables, string $tableName): \App\Shared\Infrastructure\ErDiagram\TableMetadata
    {
        foreach ($tables as $table) {
            if ($table->tableName === $tableName) {
                return $table;
            }
        }

        $this->fail(sprintf('Table "%s" not found.', $tableName));
    }

    private function findColumn(
        \App\Shared\Infrastructure\ErDiagram\TableMetadata $table,
        string $columnName,
    ): \App\Shared\Infrastructure\ErDiagram\ColumnMetadata {
        foreach ($table->columns as $column) {
            if ($column->name === $columnName) {
                return $column;
            }
        }

        $this->fail(sprintf('Column "%s" not found.', $columnName));
    }
}
