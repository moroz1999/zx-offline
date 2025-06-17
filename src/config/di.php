<?php


use App\DB\DatabaseServiceProvider;
use App\Runner\TaskRunner;
use App\Sync\ProdsSyncService;
use App\Commands\RunDaemonCommand;
use App\Commands\RunTaskCommand;
use App\Tasks\TasksRepository;
use function DI\autowire;
use function DI\create;
use function DI\get;

return [
    'databasePath' => static fn() => __DIR__ . '/../../storage/database.sqlite',
    RunDaemonCommand::class => create()->constructor(
        get(TasksRepository::class)
    ),
    RunTaskCommand::class => create()->constructor(
        get(TaskRunner::class)
    ),
    TasksRepository::class => autowire(),
    TaskRunner::class => autowire(),
    ProdsSyncService::class => autowire(),
    Doctrine\DBAL\Connection::class => DI\factory(function (DatabaseServiceProvider $db) {
        return $db->get();
    }),
    DatabaseServiceProvider::class => create()->constructor(get('databasePath')),
];
