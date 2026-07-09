<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Console\Generator;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

final class CrudRemover
{
    use GeneratorTrait;

    private const PROTECTED_CONTEXTS = ['Shared', 'User', 'Document'];

    public function __construct(
        private readonly string $projectDir,
        private readonly Filesystem $filesystem,
    ) {
    }

    public function remove(string $context, string $entity, SymfonyStyle $io): bool
    {
        if (\in_array($context, self::PROTECTED_CONTEXTS, true)) {
            $io->error(sprintf('Bounded Context "%s" is protected and cannot be modified by this command.', $context));

            return false;
        }

        $srcDir = $this->projectDir.'/src/'.$context;
        $entityPath = $srcDir.'/Domain/Entity/'.$entity.'.php';

        if (!$this->filesystem->exists($entityPath)) {
            $io->error(sprintf('Entity "%s" does not exist in Bounded Context "%s".', $entity, $context));

            return false;
        }

        $io->section('Domain');
        $this->removePaths($srcDir, $this->domainPaths($entity), $io);

        $io->section('Application');
        $this->removePaths($srcDir, $this->applicationPaths($entity), $io);

        $io->section('Infrastructure');
        $this->removePaths($srcDir, $this->infrastructurePaths($entity), $io);

        $io->section('Tests');
        $this->removePaths($this->projectDir.'/tests', $this->testPaths($context, $entity), $io);

        $io->section('Configuration');
        $this->unregisterDoctrineType($context, $entity, $io);
        $this->unregisterRepositoryAlias($context, $entity, $io);
        $this->unregisterMessengerBinding($entity, $io);

        $this->removeEmptyDirectories($srcDir, $io);
        $this->removeEmptyDirectories($this->projectDir.'/tests/Unit/'.$context, $io);
        $this->removeEmptyDirectories($this->projectDir.'/tests/Integration/'.$context, $io);

        return true;
    }

    /**
     * @return list<string>
     */
    public function domainPaths(string $entity): array
    {
        return [
            "Domain/Entity/{$entity}.php",
            "Domain/ValueObject/{$entity}Id.php",
            "Domain/ValueObject/{$entity}Status.php",
            "Domain/Repository/{$entity}RepositoryInterface.php",
            "Domain/Event/{$entity}Created.php",
            "Domain/Event/{$entity}Updated.php",
            "Domain/Event/{$entity}Replaced.php",
            "Domain/Event/{$entity}Deleted.php",
            "Domain/Exception/{$entity}NotFoundException.php",
            "Domain/Exception/{$entity}AlreadyExistsException.php",
        ];
    }

    /**
     * @return list<string>
     */
    public function applicationPaths(string $entity): array
    {
        return [
            "Application/Command/Create{$entity}",
            "Application/Command/Update{$entity}",
            "Application/Command/Replace{$entity}",
            "Application/Command/Delete{$entity}",
            "Application/Query/Get{$entity}",
            "Application/Query/Get{$entity}s",
        ];
    }

    /**
     * @return list<string>
     */
    public function infrastructurePaths(string $entity): array
    {
        return [
            "Infrastructure/Persistence/Doctrine/Mapping/{$entity}.orm.xml",
            "Infrastructure/Persistence/Doctrine/Repository/Doctrine{$entity}Repository.php",
            "Infrastructure/Persistence/Doctrine/Type/{$entity}IdType.php",
            "Infrastructure/Http/Controller/Create{$entity}Controller.php",
            "Infrastructure/Http/Controller/Get{$entity}Controller.php",
            "Infrastructure/Http/Controller/Get{$entity}sController.php",
            "Infrastructure/Http/Controller/Patch{$entity}Controller.php",
            "Infrastructure/Http/Controller/Put{$entity}Controller.php",
            "Infrastructure/Http/Controller/Delete{$entity}Controller.php",
            "Infrastructure/Http/Request/Patch{$entity}Request.php",
            "Infrastructure/Fixture/{$entity}Fixture.php",
            "Infrastructure/Messaging/{$entity}CreatedMessageHandler.php",
        ];
    }

    /**
     * @return list<string>
     */
    public function testPaths(string $context, string $entity): array
    {
        return [
            "Unit/{$context}/Domain/Entity/{$entity}Test.php",
            "Unit/{$context}/Domain/Mother/{$entity}Mother.php",
            "Unit/{$context}/Domain/Mother/{$entity}IdMother.php",
            "Unit/{$context}/Application/Create{$entity}CommandHandlerTest.php",
            "Unit/{$context}/Application/Update{$entity}CommandHandlerTest.php",
            "Unit/{$context}/Application/Delete{$entity}CommandHandlerTest.php",
            "Integration/{$context}/Infrastructure/Doctrine{$entity}RepositoryTest.php",
        ];
    }

    /**
     * @param list<string> $relativePaths
     */
    private function removePaths(string $baseDir, array $relativePaths, SymfonyStyle $io): void
    {
        foreach ($relativePaths as $relativePath) {
            $fullPath = $baseDir.'/'.$relativePath;

            if (!$this->filesystem->exists($fullPath)) {
                $io->writeln(sprintf('  <comment>skipped</comment> %s (not found)', $relativePath));

                continue;
            }

            $this->filesystem->remove($fullPath);
            $io->writeln(sprintf('  <info>removed</info> %s', $relativePath));
        }
    }

    private function unregisterDoctrineType(string $context, string $entity, SymfonyStyle $io): void
    {
        $path = $this->projectDir.'/config/packages/doctrine.yaml';
        $content = file_get_contents($path);

        if (false === $content) {
            $io->error('Could not read config/packages/doctrine.yaml.');

            return;
        }

        $typeName = $this->toSnakeCase($entity).'_id';
        $pattern = '/            '.preg_quote($typeName, '/').': App\\\\'.preg_quote($context, '/').'\\\\Infrastructure\\\\Persistence\\\\Doctrine\\\\Type\\\\'.preg_quote($entity, '/').'IdType\n/';
        $updated = preg_replace($pattern, '', $content, 1);

        if (null === $updated || $updated === $content) {
            $io->writeln(sprintf('  <comment>skipped</comment> Doctrine type %s (not registered)', $typeName));

            return;
        }

        file_put_contents($path, $updated);
        $io->writeln(sprintf('  <info>updated</info> config/packages/doctrine.yaml (removed type %s)', $typeName));
    }

    private function unregisterRepositoryAlias(string $context, string $entity, SymfonyStyle $io): void
    {
        $path = $this->projectDir.'/config/services.yaml';
        $content = file_get_contents($path);

        if (false === $content) {
            $io->error('Could not read config/services.yaml.');

            return;
        }

        $interface = 'App\\'.$context.'\\Domain\\Repository\\'.$entity.'RepositoryInterface';
        $pattern = '/    '.preg_quote($interface, '/').':\n        alias: App\\\\'.preg_quote($context, '/').'\\\\Infrastructure\\\\Persistence\\\\Doctrine\\\\Repository\\\\Doctrine'.preg_quote($entity, '/').'Repository\n/';
        $updated = preg_replace($pattern, '', $content, 1);

        if (null === $updated || $updated === $content) {
            $io->writeln(sprintf('  <comment>skipped</comment> Repository alias %s (not registered)', $entity));

            return;
        }

        file_put_contents($path, $updated);
        $io->writeln(sprintf('  <info>updated</info> config/services.yaml (removed repository %s)', $entity));
    }

    private function unregisterMessengerBinding(string $entity, SymfonyStyle $io): void
    {
        $path = $this->projectDir.'/config/packages/messenger.yaml';
        $content = file_get_contents($path);

        if (false === $content) {
            $io->error('Could not read config/packages/messenger.yaml.');

            return;
        }

        $lower = $this->toSnakeCase($entity);
        $queueName = 'events.'.$lower;
        $pattern = '/                        '.preg_quote($queueName, '/').':\n                            binding_keys: \[\''.preg_quote($lower, '/').'\.#\'\]\n/';
        $updated = preg_replace($pattern, '', $content, 1);

        if (null === $updated || $updated === $content) {
            $io->writeln(sprintf('  <comment>skipped</comment> Messenger binding %s (not registered)', $queueName));

            return;
        }

        file_put_contents($path, $updated);
        $io->writeln(sprintf('  <info>updated</info> config/packages/messenger.yaml (removed binding %s.#)', $lower));
    }

    private function removeEmptyDirectories(string $baseDir, SymfonyStyle $io): void
    {
        if (!$this->filesystem->exists($baseDir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($baseDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            if (!$item instanceof \SplFileInfo || !$item->isDir()) {
                continue;
            }

            $path = $item->getPathname();
            $entries = scandir($path);

            if (false === $entries) {
                continue;
            }

            $entries = array_diff($entries, ['.', '..']);

            if ([] !== $entries && $entries !== ['.gitkeep']) {
                continue;
            }

            $this->filesystem->remove($path);
        }
    }
}
