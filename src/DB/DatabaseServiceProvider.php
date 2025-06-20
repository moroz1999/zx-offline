<?php
declare(strict_types=1);


namespace App\DB;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

readonly class DatabaseServiceProvider
{
    public function __construct(
        private string $databasePath
    )
    {
    }

    public function get(): Connection
    {
        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'path' => $this->databasePath,
        ]);

        return $connection;
    }

}
