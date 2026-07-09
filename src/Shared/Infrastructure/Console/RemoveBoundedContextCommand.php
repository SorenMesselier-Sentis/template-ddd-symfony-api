<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Console;

use App\Shared\Infrastructure\Console\Generator\BoundedContextRemover;
use App\Shared\Infrastructure\Console\Generator\GeneratorTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'remove:bounded-context', description: 'Remove a bounded context and all its entities')]
final class RemoveBoundedContextCommand extends Command
{
    use GeneratorTrait;

    public function __construct(
        private readonly BoundedContextRemover $boundedContextRemover,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            name: 'name',
            mode: InputArgument::REQUIRED,
            description: 'The bounded context name to remove (e.g. Product, Order)',
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
        $name = $this->argumentAsString($input, 'name');
        $force = (bool) $input->getOption('force');

        $io->title(sprintf('Removing Bounded Context: %s', $name));

        if (!$force && !$io->confirm(sprintf('This will permanently delete "%s" and all its entities. Continue?', $name), false)) {
            $io->warning('Aborted.');

            return Command::SUCCESS;
        }

        if (!$this->boundedContextRemover->remove($name, $io)) {
            return Command::FAILURE;
        }

        $io->success(sprintf('Bounded Context "%s" removed successfully.', $name));

        return Command::SUCCESS;
    }
}
