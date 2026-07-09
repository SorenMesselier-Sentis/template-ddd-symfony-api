<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Console;

use App\Shared\Infrastructure\Console\Generator\BoundedContextGenerator;
use App\Shared\Infrastructure\Console\Generator\GeneratorTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(name: 'make:bounded-context', description: 'Generate a DDD bounded context skeleton (structure and configuration)')]
final class MakeBoundedContextCommand extends Command
{
    use GeneratorTrait;

    private SymfonyStyle $io;

    public function __construct(
        private readonly string $projectDir,
        private readonly Filesystem $filesystem,
        private readonly BoundedContextGenerator $boundedContextGenerator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            name: 'name',
            mode: InputArgument::REQUIRED,
            description: 'The name of the Bounded Context (e.g. Product, Order)',
        );
        $this->addOption(
            name: 'api-version',
            mode: InputOption::VALUE_REQUIRED,
            description: 'API version route block (e.g. v1, v2)',
            default: 'v1',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $name = $this->argumentAsString($input, 'name');
        $version = $input->getOption('api-version');
        $version = \is_string($version) ? $version : 'v1';
        $srcDir = $this->projectDir.'/src/'.$name;

        $this->io->title(sprintf('Generating Bounded Context: %s', $name));

        if ($this->filesystem->exists($srcDir)) {
            $this->io->error(sprintf('Bounded Context "%s" already exists.', $name));

            return Command::FAILURE;
        }

        if (!$this->apiVersionExists($this->projectDir, $version)) {
            $this->io->error(sprintf('API version "%s" is not registered in config/routes.yaml (expected key api_%s).', $version, $version));

            return Command::FAILURE;
        }

        $this->boundedContextGenerator->generate($name, $version, $this->io);
        $this->printNextSteps($name);

        $this->io->success(sprintf('Bounded Context "%s" generated successfully.', $name));

        return Command::SUCCESS;
    }

    private function printNextSteps(string $name): void
    {
        $this->io->section('Next steps');
        $this->io->listing([
            sprintf('Generate a CRUD entity with <info>make crud context=%s entity=YourEntity</info>', $name),
            'Or manually add domain entities following docs/ddd-conventions.md',
            sprintf('Create an <info>%sExceptionMapper</info> when you add custom exceptions', $name),
        ]);
    }
}
