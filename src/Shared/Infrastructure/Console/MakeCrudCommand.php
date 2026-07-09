<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Console;

use App\Shared\Infrastructure\Console\Generator\CrudGenerator;
use App\Shared\Infrastructure\Console\Generator\GeneratorTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'make:bc-crud', description: 'Generate a CRUD entity inside an existing bounded context')]
final class MakeCrudCommand extends Command
{
    use GeneratorTrait;

    public function __construct(
        private readonly CrudGenerator $crudGenerator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            name: 'context',
            mode: InputArgument::REQUIRED,
            description: 'The bounded context name (e.g. Product, Order)',
        );
        $this->addArgument(
            name: 'entity',
            mode: InputArgument::REQUIRED,
            description: 'The entity name to generate (e.g. Product, OrderLine)',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = $this->argumentAsString($input, 'context');
        $entity = $this->argumentAsString($input, 'entity');

        $io->title(sprintf('Generating CRUD: %s in %s', $entity, $context));

        if (!$this->crudGenerator->generate($context, $entity, $io)) {
            return Command::FAILURE;
        }

        $io->success(sprintf('CRUD for "%s" in "%s" generated successfully.', $entity, $context));

        return Command::SUCCESS;
    }
}
