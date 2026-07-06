<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\ErDiagram;

use App\Shared\Infrastructure\ErDiagram\DiagramWriter;
use App\Shared\Infrastructure\ErDiagram\ErDiagramValidator;
use App\Shared\Infrastructure\ErDiagram\MermaidRenderer;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Filesystem\Filesystem;

final class ErDiagramIntegrationTest extends UnitTestCase
{
    public function testItGeneratesCompleteProjectDiagram(): void
    {
        $projectDir = \dirname(__DIR__, 5);
        $parser = new \App\Shared\Infrastructure\ErDiagram\DoctrineXmlParser();
        $renderer = new MermaidRenderer();
        $validator = new ErDiagramValidator();

        $entities = $parser->parseAll($projectDir, static function (): void {});
        $mermaid = $renderer->render($entities);

        $validator->assertComplete($mermaid, $entities);

        $this->assertStringContainsString('users {', $mermaid);
        $this->assertStringContainsString('refresh_tokens {', $mermaid);
        $this->assertStringContainsString('documents {', $mermaid);
        $this->assertStringContainsString('multipart_upload_sessions {', $mermaid);
        $this->assertStringContainsString('hashed_password password', $mermaid);
    }

    public function testDiagramWriterCreatesFileWithHeader(): void
    {
        $tempDir = sys_get_temp_dir().'/er-diagram-writer-'.uniqid('', true);
        mkdir($tempDir);

        $writer = new DiagramWriter(new Filesystem());
        $mermaid = "```mermaid\nerDiagram\n    users {\n        string id\n    }\n```\n";
        $relativePath = $writer->write($tempDir, $mermaid);

        $this->assertSame('docs/er-diagram.md', $relativePath);
        $content = file_get_contents($tempDir.'/docs/er-diagram.md');
        $this->assertSame("# Entity Relation Diagram\n\n".$mermaid, $content);

        unlink($tempDir.'/docs/er-diagram.md');
        rmdir($tempDir.'/docs');
        rmdir($tempDir);
    }
}
