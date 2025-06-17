<?php
declare(strict_types=1);

namespace App\DB;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;

readonly class MigrationRunner
{
    public function __construct(
    )
    {
    }

    /**
     * @throws Exception
     */
    public function migrateIfNeeded(Connection $connection): void
    {
        $sm = $connection->createSchemaManager();

        if (!$sm->tablesExist(['tasks'])) {
            $schema = new Schema();
            $tasks = $schema->createTable('tasks');

            $tasks->addColumn('id', 'string')->setNotnull(true);
            $tasks->addColumn('type', 'string');
            $tasks->addColumn('target_id', 'string', ['notnull' => false]);
            $tasks->addColumn('status', 'string', ['default' => 'todo']);
            $tasks->addColumn('attempts', 'integer', ['default' => 0]);
            $tasks->addColumn('last_error', 'text', ['notnull' => false]);
            $tasks->addColumn('created_at', 'datetime');
            $tasks->addColumn('updated_at', 'datetime');

            $platform = $connection->getDatabasePlatform();
            $queries = $schema->toSql($platform);

            foreach ($queries as $sql) {
                $connection->executeStatement($sql);
            }
        }
    }
}
