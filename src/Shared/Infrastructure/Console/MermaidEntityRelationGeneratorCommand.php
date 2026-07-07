<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Console;

use App\Shared\Infrastructure\ErDiagram\DiagramWriter;
use App\Shared\Infrastructure\ErDiagram\ErDiagramValidator;
use App\Shared\Infrastructure\ErDiagram\MermaidRenderer;
use App\Shared\Infrastructure\ErDiagram\MigrationParser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:generate:er-diagram', description: 'Generate the Entity Relation schema from Doctrine migrations')]
final class MermaidEntityRelationGeneratorCommand extends Command
{
    public function __construct(
        private readonly string $projectDir,
        private readonly MigrationParser $migrationParser,
        private readonly MermaidRenderer $mermaidRenderer,
        private readonly DiagramWriter $diagramWriter,
        private readonly ErDiagramValidator $erDiagramValidator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('output', null, InputOption::VALUE_REQUIRED, 'Output path of the diagram')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Display the diagram without writing to a file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stderr = $output instanceof ConsoleOutputInterface
            ? $output->getErrorOutput()
            : $output;

        $writeWarning = static function (string $message) use ($stderr): void {
            $stderr->writeln($message);
        };

        try {
            $migrationFiles = $this->migrationParser->discoverMigrationFiles($this->projectDir);

            if ([] === $migrationFiles) {
                $output->writeln('No migration file found. No diagram generated.');

                return Command::SUCCESS;
            }

            $tables = $this->migrationParser->parseAll($this->projectDir, $writeWarning);

            if ([] === $tables) {
                $output->writeln('No migration file found. No diagram generated.');

                return Command::SUCCESS;
            }

            $mermaidBlock = $this->mermaidRenderer->render($tables);
            $this->erDiagramValidator->assertComplete($mermaidBlock, $tables);

            if (true === $input->getOption('dry-run')) {
                $output->write($mermaidBlock);

                return Command::SUCCESS;
            }

            $outputPath = $input->getOption('output');
            $relativePath = $this->diagramWriter->write(
                $this->projectDir,
                $mermaidBlock,
                \is_string($outputPath) ? $outputPath : null,
            );
            $output->writeln($relativePath);

            return Command::SUCCESS;
        } catch (\Throwable $exception) {
            $stderr->writeln($exception->getMessage());

            return Command::FAILURE;
        }
    }
}
