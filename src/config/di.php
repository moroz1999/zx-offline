<?php


use App\DB\DatabaseServiceProvider;
use App\DB\Migrations\Schema;
use App\Runner\TaskRunner;
use App\Sync\ProductSyncService;
use App\Commands\RunDaemonCommand;
use App\Commands\RunTaskCommand;
use App\Tasks\TasksService;
use function DI\factory;
use function DI\autowire;
use function DI\create;
use function DI\get;

return [
    'databasePath' => static fn() => __DIR__ . '/../../storage/database.sqlite',
    RunDaemonCommand::class => create()->constructor(
        get(TasksService::class)
    ),
    RunTaskCommand::class => create()->constructor(
        get(TaskRunner::class)
    ),
    TasksService::class => autowire(),
    TaskRunner::class => autowire(),
    ProductSyncService::class => autowire(),
    Doctrine\DBAL\Connection::class => DI\factory(function (DatabaseServiceProvider $db) {
        return $db->get();
    }),
    DatabaseServiceProvider::class => create()->constructor(get('databasePath')),
];
