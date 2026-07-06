<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\ErDiagram;

use Symfony\Component\Filesystem\Filesystem;

final class DiagramWriter
{
    private const string DEFAULT_OUTPUT = 'docs/er-diagram.md';
    private const string HEADER = '# Entity Relation Diagram';

    public function __construct(
        private readonly Filesystem $filesystem,
    ) {
    }

    public function write(string $projectDir, string $mermaidBlock, ?string $outputPath = null): string
    {
        $relativePath = $outputPath ?? self::DEFAULT_OUTPUT;
        $absolutePath = $this->resolvePath($projectDir, $relativePath);
        $directory = \dirname($absolutePath);

        if (!$this->filesystem->exists($directory)) {
            $this->filesystem->mkdir($directory);
        }

        $content = self::HEADER."\n\n".$mermaidBlock;

        try {
            $this->filesystem->dumpFile($absolutePath, $content);
        } catch (\Throwable $exception) {
            throw new \RuntimeException(sprintf('Impossible to write file %s: %s', $absolutePath, $exception->getMessage()), previous: $exception);
        }

        return $relativePath;
    }

    private function resolvePath(string $projectDir, string $path): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return rtrim($projectDir, '/').'/'.$path;
    }
}
