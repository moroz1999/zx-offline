<?php


use App\Archive\FileArchiveService;
use App\DB\DatabaseServiceProvider;
use App\Logging\LoggerHolder;
use GuzzleHttp\Client;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
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
    ),
    Logger::class => static function (): Logger {
        $logPath = __DIR__ . '/../../logs/app.log';

        $handler = new StreamHandler($logPath, Level::Warning);

        $formatter = new LineFormatter(null, 'Y-m-d H:i:s', true, true);
        $handler->setFormatter($formatter);

        $logger = new Logger('app');
        $logger->pushHandler($handler);

        return $logger;
    },
];
