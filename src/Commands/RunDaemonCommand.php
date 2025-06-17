<?php
declare(strict_types=1);

namespace App\Commands;

use App\Tasks\TasksService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunDaemonCommand extends Command
{
    public function __construct(
        private readonly TasksService $tasksService,
    )
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('run');
        $this->setDescription('Start synchronization with ZX-Art')
            ->addOption('forever', null, InputOption::VALUE_NONE, 'Endless loop');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $forever = $input->getOption('forever');

        do {
            $task = $this->tasksService->getTask();

            if ($task) {
                $this->tasksService->lockTask($task);
                $output->writeln("Executing task {$task->id}");
                passthru("php cli.php run:task {$task->id}");
            } else {
                if ($forever) {
                    sleep(5);
                }
            }
        } while ($forever || $task);

        return Command::SUCCESS;
    }
}
