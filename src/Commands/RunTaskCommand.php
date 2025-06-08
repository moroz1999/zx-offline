<?php
declare(strict_types=1);

namespace App\Commands;

use App\Runner\TaskRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

final class RunTaskCommand extends Command
{
    public function __construct(
        private readonly TaskRunner $runner
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('run:task');
        $this->setDescription('Runs task by ID')
            ->addArgument('id', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument('id');

        try {
            $this->runner->run($id);
            $output->writeln("Task $id выполнена.");
            return Command::SUCCESS;
        } catch (Throwable $e) {
            $output->writeln("<error>Error: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }
    }
}
