<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Console;

use App\Shared\Infrastructure\Messaging\Outbox\OutboxRelay;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:outbox:relay', description: 'Publish unpublished outbox events to the event bus.')]
final class OutboxRelayCommand extends Command
{
    public function __construct(
        private readonly OutboxRelay $outboxRelay,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Max events to relay per run.', '100');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $rawLimit = $input->getOption('limit');

        if (!\is_int($rawLimit) && !(\is_string($rawLimit) && '' !== $rawLimit && ctype_digit($rawLimit))) {
            $io->error('Option --limit must be a positive integer.');

            return Command::FAILURE;
        }

        $count = $this->outboxRelay->relay(max(1, (int) $rawLimit));

        $io->success(sprintf('Published %d outbox event(s).', $count));

        return Command::SUCCESS;
    }
}
