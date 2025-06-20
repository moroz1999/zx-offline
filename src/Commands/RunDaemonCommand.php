<?php
declare(strict_types=1);

namespace App\Commands;

use App\Tasks\TasksRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunDaemonCommand extends Command
{
    public function __construct(
        private readonly TasksRepository $tasksService,
    )
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('run:daemon');
        $this->setDescription('Runs daemon to execute pending tasks');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        do {
            $task = $this->tasksService->getNextTask();

            if ($task) {
                $this->tasksService->lockTask($task);
                $output->writeln("{$task->id} $task->type" . ($task->targetId ? " $task->targetId" : "") . " executed from cli");
                passthru("php cli.php run:task {$task->id}");
            }
        } while ($task);

        return Command::SUCCESS;
    }
}
