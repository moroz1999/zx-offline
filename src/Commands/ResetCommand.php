<?php
declare(strict_types=1);

namespace App\Commands;

use App\DB\SchemaException;
use App\DB\SchemaService;
use App\Logging\IoLogger;
use App\Logging\LoggerHolder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ResetCommand extends Command
{
    public function __construct(
        private readonly SchemaService $schemaService,
        private readonly LoggerHolder  $loggerHolder
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('reset')
            ->setDescription('Drop all tables to empty state');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $logger = new IoLogger($io);
        $this->loggerHolder->setIoLogger($logger);

        $io->section('Dropping tables');
        try {
            $this->schemaService->dropBase();
        } catch (SchemaException $e) {
            $this->loggerHolder->error($e->getMessage());;
        }
        $io->section('Creating tables');
        try {
            $this->schemaService->createIfNeeded();
        } catch (SchemaException $e) {
            $this->loggerHolder->error($e->getMessage());;
        }

        return Command::SUCCESS;
    }
}
