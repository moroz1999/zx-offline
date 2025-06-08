<?php


use App\DB\DatabaseServiceProvider;
use App\Runner\TaskRunner;
use App\Sync\ProductSyncService;
use App\Commands\RunWorkerCommand;
use App\Commands\RunTaskCommand;

return [

    RunWorkerCommand::class => DI\create()->constructor(DI\get(TaskRunner::class)),
    RunTaskCommand::class => DI\create()->constructor(DI\get(TaskRunner::class)),

    TaskRunner::class => DI\autowire(),
    ProductSyncService::class => DI\autowire(),

    DatabaseServiceProvider::class => DI\factory([DatabaseServiceProvider::class, 'init']),
];
