<?php
declare(strict_types=1);

namespace App\Commands;

use App\Tasks\TasksRepository;
use App\Tasks\TaskTypes;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateCommand extends Command
{
    public function __construct(
        private readonly TasksRepository $tasksService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('update')
            ->setDescription('Start synchronization with ZX-Art');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->section('Adding sync_prods task...');
        $this->tasksService->addTask(TaskTypes::sync_prods, null);
        $io->success('Task sync_prods added.');

        $io->section('Starting daemon...');
        $command = $this->getApplication()?->find('run:daemon');
        if (!$command) {
            return Command::FAILURE;
        }

        $args = new ArrayInput([]);
        return $command->run($args, $output);
    }
}
