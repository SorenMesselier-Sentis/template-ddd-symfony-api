<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\ErDiagram;

use App\Shared\Infrastructure\ErDiagram\DoctrineXmlParser;
use App\Tests\Unit\UnitTestCase;

final class DoctrineXmlParserTest extends UnitTestCase
{
    private DoctrineXmlParser $parser;
    private string $projectDir;

    protected function setUp(): void
    {
        $this->parser = new DoctrineXmlParser();
        $this->projectDir = \dirname(__DIR__, 5);
    }

    public function testItDiscoversAllProjectMappingFiles(): void
    {
        $files = $this->parser->discoverMappingFiles($this->projectDir);

        $this->assertCount(4, $files);
        $this->assertStringEndsWith('Document.orm.xml', $files[0]);
        $this->assertStringEndsWith('MultipartUploadSession.orm.xml', $files[1]);
        $this->assertStringEndsWith('RefreshToken.orm.xml', $files[2]);
        $this->assertStringEndsWith('User.orm.xml', $files[3]);
    }

    public function testItParsesUserEntityColumnsAndCustomTypes(): void
    {
        $warnings = [];
        $entities = $this->parser->parseAll($this->projectDir, static function (string $message) use (&$warnings): void {
            $warnings[] = $message;
        });

        $this->assertSame([], $warnings);
        $this->assertCount(4, $entities);

        $users = $this->findEntityByTable($entities, 'users');
        $this->assertSame('App\User\Domain\Entity\User', $users->entityFqcn);
        $this->assertSame('user_id', $this->findColumn($users, 'id')->type);
        $this->assertSame('email', $this->findColumn($users, 'email')->type);
        $this->assertSame('hashed_password', $this->findColumn($users, 'password')->type);
        $this->assertSame('user_roles', $this->findColumn($users, 'roles')->type);
    }

    public function testItIgnoresMalformedXmlWithWarning(): void
    {
        $tempDir = sys_get_temp_dir().'/er-diagram-parser-'.uniqid('', true);
        $mappingDir = $tempDir.'/src/Foo/Infrastructure/Persistence/Doctrine/Mapping';
        mkdir($mappingDir, 0o777, true);

        $invalidFile = $mappingDir.'/Broken.orm.xml';
        file_put_contents($invalidFile, '<not-xml');

        $warnings = [];
        $entities = $this->parser->parseAll($tempDir, static function (string $message) use (&$warnings): void {
            $warnings[] = $message;
        });

        $this->assertSame([], $entities);
        $this->assertCount(1, $warnings);
        $this->assertStringContainsString($invalidFile, $warnings[0]);
        $this->assertStringContainsString('malformé', $warnings[0]);

        unlink($invalidFile);
        rmdir($mappingDir);
        rmdir(\dirname($mappingDir, 4));
        rmdir(\dirname($mappingDir, 3));
        rmdir(\dirname($mappingDir, 2));
        rmdir(\dirname($mappingDir, 1));
        rmdir($tempDir.'/src/Foo');
        rmdir($tempDir.'/src');
        rmdir($tempDir);
    }

    public function testItIgnoresMappingWithoutValidEntity(): void
    {
        $tempDir = sys_get_temp_dir().'/er-diagram-parser-'.uniqid('', true);
        $mappingDir = $tempDir.'/src/Foo/Infrastructure/Persistence/Doctrine/Mapping';
        mkdir($mappingDir, 0o777, true);

        $invalidFile = $mappingDir.'/Empty.orm.xml';
        file_put_contents($invalidFile, <<<'XML'
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping">
    <entity name="" table=""/>
</doctrine-mapping>
XML);

        $warnings = [];
        $entities = $this->parser->parseAll($tempDir, static function (string $message) use (&$warnings): void {
            $warnings[] = $message;
        });

        $this->assertSame([], $entities);
        $this->assertCount(1, $warnings);
        $this->assertStringContainsString($invalidFile, $warnings[0]);

        unlink($invalidFile);
        rmdir($mappingDir);
        rmdir(\dirname($mappingDir, 4));
        rmdir(\dirname($mappingDir, 3));
        rmdir(\dirname($mappingDir, 2));
        rmdir(\dirname($mappingDir, 1));
        rmdir($tempDir.'/src/Foo');
        rmdir($tempDir.'/src');
        rmdir($tempDir);
    }

    /**
     * @param list<\App\Shared\Infrastructure\ErDiagram\EntityMetadata> $entities
     */
    private function findEntityByTable(array $entities, string $tableName): \App\Shared\Infrastructure\ErDiagram\EntityMetadata
    {
        foreach ($entities as $entity) {
            if ($entity->tableName === $tableName) {
                return $entity;
            }
        }

        $this->fail(sprintf('Entity with table "%s" not found.', $tableName));
    }

    private function findColumn(
        \App\Shared\Infrastructure\ErDiagram\EntityMetadata $entity,
        string $columnName,
    ): \App\Shared\Infrastructure\ErDiagram\ColumnMetadata {
        foreach ($entity->columns as $column) {
            if ($column->name === $columnName) {
                return $column;
            }
        }

        $this->fail(sprintf('Column "%s" not found.', $columnName));
    }
}
