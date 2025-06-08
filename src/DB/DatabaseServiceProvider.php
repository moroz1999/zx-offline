<?php
declare(strict_types=1);


namespace App\DB;

use App\DB\Migrations\TasksTableMigration;
use Illuminate\Database\Capsule\Manager as Capsule;

class DatabaseServiceProvider
{
    public function __construct(
        private readonly Capsule             $capsule,
        private readonly TasksTableMigration $migration,
        private readonly string              $databasePath
    )
    {
        $this->capsule->addConnection([
            'driver' => 'sqlite',
            'database' => $this->databasePath,
            'prefix' => '',
        ]);

        $this->capsule->setAsGlobal();
        $this->capsule->bootEloquent();

        $this->migration->up();
    }
}
