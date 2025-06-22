<?php
declare(strict_types=1);

namespace App\Commands;

use App\Logging\IoLogger;
use App\Logging\LoggerHolder;
use App\Tasks\TasksRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RunDaemonCommand extends Command
{
    public function __construct(
        private readonly TasksRepository $tasksService,
        private readonly LoggerHolder    $loggerHolder,
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
        $io = new SymfonyStyle($input, $output);
        $logger = new IoLogger($io);
        $this->loggerHolder->setIoLogger($logger);

        do {
            $task = $this->tasksService->getNextTask();

            if ($task) {
                $this->tasksService->lockTask($task);
                $this->loggerHolder->debug("{$task->id} $task->type" . ($task->targetId ? " $task->targetId" : "") . " executed from cli");
                passthru("php cli.php run:task {$task->id} --ansi");
            }
        } while ($task);

        return Command::SUCCESS;
    }
}
