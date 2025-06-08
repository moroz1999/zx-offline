<?php
declare(strict_types=1);

namespace App\Commands;

use Illuminate\Database\Capsule\Manager as DB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunWorkerCommand extends Command
{
    protected static string $defaultName = 'run:worker';

    protected function configure()
    {
        $this->setDescription('Запускает воркер')
            ->addOption('forever', null, InputOption::VALUE_NONE, 'Крутиться бесконечно');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $forever = $input->getOption('forever');

        do {
            $task = DB::table('tasks')->where('status', 'todo')->orderBy('created_at')->first();

            if ($task) {
                DB::table('tasks')->where('id', $task->id)->update(['status' => 'in_progress']);
                $output->writeln("Выполняем задачу {$task->id}");
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
