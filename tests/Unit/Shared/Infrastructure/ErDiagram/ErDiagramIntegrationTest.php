<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\ErDiagram;

use App\Shared\Infrastructure\ErDiagram\DiagramWriter;
use App\Shared\Infrastructure\ErDiagram\ErDiagramValidator;
use App\Shared\Infrastructure\ErDiagram\ForeignKeyRelationInferrer;
use App\Shared\Infrastructure\ErDiagram\MermaidRenderer;
use App\Shared\Infrastructure\ErDiagram\MigrationParser;
use App\Shared\Infrastructure\ErDiagram\PivotTableResolver;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Filesystem\Filesystem;

final class ErDiagramIntegrationTest extends UnitTestCase
{
    public function testItGeneratesCompleteProjectDiagram(): void
    {
        $projectDir = \dirname(__DIR__, 5);
        $parser = new MigrationParser(new ForeignKeyRelationInferrer(), new PivotTableResolver());
        $renderer = new MermaidRenderer();
        $validator = new ErDiagramValidator();

        $tables = $parser->parseAll($projectDir, static function (): void {});
        $mermaid = $renderer->render($tables);

        $validator->assertComplete($mermaid, $tables);

        $this->assertStringContainsString('users {', $mermaid);
        $this->assertStringContainsString('refresh_tokens {', $mermaid);
        $this->assertStringContainsString('documents {', $mermaid);
        $this->assertStringContainsString('multipart_upload_sessions {', $mermaid);
        $this->assertStringContainsString('outbox_messages {', $mermaid);
        $this->assertStringContainsString('varchar100 first_name', $mermaid);
        $this->assertStringContainsString('varchar255 password', $mermaid);
        $this->assertStringContainsString('refresh_tokens }o--|| users : "user_id"', $mermaid);
        $this->assertStringContainsString('documents }o--|| users : "owner_id"', $mermaid);
        $this->assertStringContainsString('multipart_upload_sessions }o--|| documents : "document_id"', $mermaid);
        $this->assertStringContainsString('multipart_upload_sessions }o--|| users : "owner_id"', $mermaid);
    }

    public function testDiagramWriterCreatesFileWithHeader(): void
    {
        $tempDir = sys_get_temp_dir().'/er-diagram-writer-'.uniqid('', true);
        mkdir($tempDir);

        $writer = new DiagramWriter(new Filesystem());
        $mermaid = "```mermaid\nerDiagram\n    users {\n        uuid id\n    }\n```\n";
        $relativePath = $writer->write($tempDir, $mermaid);

        $this->assertSame('docs/er-diagram.md', $relativePath);
        $content = file_get_contents($tempDir.'/docs/er-diagram.md');
        $this->assertSame("# Entity Relation Diagram\n\n".$mermaid, $content);

        unlink($tempDir.'/docs/er-diagram.md');
        rmdir($tempDir.'/docs');
        rmdir($tempDir);
    }
}
