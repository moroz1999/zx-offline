<?php
declare(strict_types=1);

namespace App\Commands;

use App\Logging\IoLogger;
use App\Logging\LoggerHolder;
use App\Tasks\TaskException;
use App\Tasks\TasksRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ResumeCommand extends Command
{

    public function __construct(
        private readonly TasksRepository $tasksService,
        private readonly LoggerHolder $loggerHolder
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('resume')
            ->setDescription('Resume existing queue tasks');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $logger = new IoLogger($io);
        $this->loggerHolder->setIoLogger($logger);

        try {
            $requeuedTasks = $this->tasksService->requeueInProgressTasks();
        } catch (TaskException $e) {
            $this->loggerHolder->error($e->getMessage());
            return Command::FAILURE;
        }

        if ($requeuedTasks > 0) {
            $io->note(sprintf(
                'Returned %d interrupted task%s to the queue.',
                $requeuedTasks,
                $requeuedTasks === 1 ? '' : 's',
            ));
        }

        $io->section('Starting daemon...');
        $command = $this->getApplication()?->find('run:daemon');
        if (!$command) {
            return Command::FAILURE;
        }

        $args = new ArrayInput([]);
        return $command->run($args, $output);
    }
}
