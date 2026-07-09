<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Console\Generator;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

trait GeneratorTrait
{
    /**
     * @param array<string, string> $files
     */
    private function writeFiles(Filesystem $filesystem, string $baseDir, array $files, SymfonyStyle $io): void
    {
        foreach ($files as $relativePath => $content) {
            $fullPath = $baseDir.'/'.$relativePath;
            $filesystem->dumpFile($fullPath, $content);
            $io->writeln(sprintf('  <info>created</info> %s', $relativePath));
        }
    }

    private function toSnakeCase(string $name): string
    {
        return strtolower(preg_replace('/[A-Z]/', '_$0', lcfirst($name)) ?? $name);
    }

    private function argumentAsString(InputInterface $input, string $name): string
    {
        $value = $input->getArgument($name);

        if (!\is_string($value)) {
            return '';
        }

        return ucfirst($value);
    }

    private function apiVersionExists(string $projectDir, string $version): bool
    {
        $routesPath = $projectDir.'/config/routes.yaml';
        $content = file_get_contents($routesPath);

        if (false === $content) {
            return false;
        }

        return (bool) preg_match('/^api_'.preg_quote($version, '/').':/m', $content);
    }
}
