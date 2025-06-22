<?php


use App\Archive\FileArchiveService;
use App\DB\DatabaseServiceProvider;
use App\Logging\LoggerHolder;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use function DI\create;
use function DI\get;

return [
    LoggerInterface::class => get(LoggerHolder::class),
    'archiveBasePath' => static fn() => __DIR__ . '/../../files/',
    'databasePath' => static fn() => __DIR__ . '/../../storage/database.sqlite',
    Doctrine\DBAL\Connection::class => DI\factory(function (DatabaseServiceProvider $db) {
        return $db->get();
    }),
    DatabaseServiceProvider::class => create()->constructor(get('databasePath')),
    FileArchiveService::class => create()->constructor(get('archiveBasePath')),
    Client::class => create()->constructor([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36'
            ]
        ]
    )
];
