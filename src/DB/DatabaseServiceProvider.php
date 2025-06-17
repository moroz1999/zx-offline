<?php
declare(strict_types=1);


namespace App\DB;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\PhpFile;
use Doctrine\Migrations\DependencyFactory;

readonly class DatabaseServiceProvider
{
    public function __construct(
        private string $databasePath
    )
    {
    }

    public function get(): Connection
    {
        $conn = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'path' => $this->databasePath,
        ]);

        $dependencyFactory = DependencyFactory::fromConnection(
            new PhpFile(__DIR__ . '/../config/migrations.php'),
            new ExistingConnection($conn)
        );

        $runner = new MigrationRunner($dependencyFactory);
        $runner->migrateIfNeeded();

        return $conn;
    }

}
