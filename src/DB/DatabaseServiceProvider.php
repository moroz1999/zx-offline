<?php
declare(strict_types=1);


namespace App\DB;

use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Connection;

readonly class DatabaseServiceProvider
{
    public function __construct(
        private string $databasePath
    )
    {
    }

    public function get(): Connection
    {
        $capsule = new Manager();

        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => $this->databasePath,
            'prefix' => '',
        ]);

        $capsule->bootEloquent();
        return $capsule->getConnection();
    }
}
