<?php
declare(strict_types=1);

namespace App\Commands;

use App\Logging\IoLogger;
use App\Logging\LoggerHolder;
use App\Runner\TaskRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

final class RunTaskCommand extends Command
{
    public function __construct(
        private readonly TaskRunner   $runner,
        private readonly LoggerHolder $loggerHolder,
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
        $io = new SymfonyStyle($input, $output);
        $logger = new IoLogger($io);
        $this->loggerHolder->setIoLogger($logger);

        try {
            $task = $this->runner->run($id);
            $output->writeln("Task $task->type" . ($task->targetId ? ", $task->targetId" : "") . " ($task->id) executed successfully.");
            return Command::SUCCESS;
        } catch (Throwable $e) {
            $output->writeln("<error>Error: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }
    }
}
