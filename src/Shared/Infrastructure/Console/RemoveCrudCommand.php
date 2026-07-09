<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Console;

use App\Shared\Infrastructure\Console\Generator\CrudRemover;
use App\Shared\Infrastructure\Console\Generator\GeneratorTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'remove:bc-crud', description: 'Remove a CRUD entity from an existing bounded context')]
final class RemoveCrudCommand extends Command
{
    use GeneratorTrait;

    public function __construct(
        private readonly CrudRemover $crudRemover,
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
            description: 'The entity name to remove (e.g. Product, OrderLine)',
        );
        $this->addOption(
            name: 'force',
            shortcut: 'f',
            mode: InputOption::VALUE_NONE,
            description: 'Skip confirmation prompt',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = $this->argumentAsString($input, 'context');
        $entity = $this->argumentAsString($input, 'entity');
        $force = (bool) $input->getOption('force');

        $io->title(sprintf('Removing CRUD: %s from %s', $entity, $context));

        if (!$force && !$io->confirm(sprintf('This will permanently delete "%s" and its configuration. Continue?', $entity), false)) {
            $io->warning('Aborted.');

            return Command::SUCCESS;
        }

        if (!$this->crudRemover->remove($context, $entity, $io)) {
            return Command::FAILURE;
        }

        $io->success(sprintf('CRUD for "%s" in "%s" removed successfully.', $entity, $context));

        return Command::SUCCESS;
    }
}
