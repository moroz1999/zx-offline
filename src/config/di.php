<?php


use App\Commands\RunDaemonCommand;
use App\Commands\RunTaskCommand;
use App\DB\DatabaseServiceProvider;
use App\Logging\LoggerHolder;
use App\Runner\TaskRunner;
use App\Tasks\TasksRepository;
use Psr\Log\LoggerInterface;
use function DI\autowire;
use function DI\create;
use function DI\get;

return [
    LoggerInterface::class => get(LoggerHolder::class),
    'databasePath' => static fn() => __DIR__ . '/../../storage/database.sqlite',
    Doctrine\DBAL\Connection::class => DI\factory(function (DatabaseServiceProvider $db) {
        return $db->get();
    }),
    DatabaseServiceProvider::class => create()->constructor(get('databasePath')),
];
