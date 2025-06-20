<?php
declare(strict_types=1);

namespace App\DB;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;

readonly class SchemaService
{
    private AbstractSchemaManager $sm;

    public function __construct(
        private Connection $connection,
    )
    {
        $this->sm = $connection->createSchemaManager();
    }

    public function dropBase(): void
    {
        try {
            $this->dropTable(Tables::zx_prods);
            $this->dropTable(Tables::tasks);
            $this->dropTable(Tables::zx_releases);
            $this->dropTable(Tables::files);
        } catch (Exception $e) {
            throw new SchemaException("Error dropping database schema: {$e->getMessage()}");
        }
    }

    /**
     * @throws Exception
     */
    private function dropTable(Tables $tableName): void
    {
        if ($this->sm->tablesExist([$tableName->name])) {
            $schema = new Schema();
            $schema->dropTable($tableName->name);
            $platform = $this->connection->getDatabasePlatform();
            $queries = $schema->toSql($platform);
            foreach ($queries as $query) {
                $this->connection->executeStatement($query);
            }
        }
    }

    public function createIfNeeded(): void
    {
        try {
            $this->checkTasksTable();
            $this->checkProdsTable();
            $this->checkReleasesTable();
        } catch (Exception $e) {
            throw new SchemaException("Error creating database schema: {$e->getMessage()}");
        }
    }

    /**
     * @throws Exception
     */
    public function checkTasksTable(): void
    {
        if (!$this->sm->tablesExist([Tables::tasks->name])) {
            $schema = new Schema();
            $tasks = $schema->createTable(Tables::tasks->name);

            $tasks->addColumn('id', 'string')->setNotnull(true);
            $tasks->addColumn('type', 'string');
            $tasks->addColumn('target_id', 'string', ['notnull' => false]);
            $tasks->addColumn('status', 'string', ['default' => 'todo']);
            $tasks->addColumn('attempts', 'integer', ['default' => 0]);
            $tasks->addColumn('last_error', 'text', ['notnull' => false]);
            $tasks->addColumn('created_at', 'datetime');

            $platform = $this->connection->getDatabasePlatform();
            $queries = $schema->toSql($platform);

            foreach ($queries as $sql) {
                $this->connection->executeStatement($sql);
            }
        }
    }


    /**
     * @throws Exception
     */
    private function checkProdsTable(): void
    {
        if (!$this->sm->tablesExist([Tables::zx_prods->name])) {
            $schema = new Schema();
            $prods = $schema->createTable(Tables::zx_prods->name);

            $prods->addColumn('id', 'integer')->setNotnull(true);
            $prods->setPrimaryKey(['id']);

            $prods->addColumn('title', 'string');
            $prods->addColumn('date_modified', 'integer');
            $prods->addColumn('legal_status', 'string', ['notnull' => false]);

            $prods->addColumn('category_id', 'integer', ['notnull' => false]);
            $prods->addColumn('category_title', 'string', ['notnull' => false]);

            $platform = $this->connection->getDatabasePlatform();
            foreach ($schema->toSql($platform) as $sql) {
                $this->connection->executeStatement($sql);
            }
        }
    }

    /**
     * @throws Exception
     */
    private function checkReleasesTable(): void
    {
        if (!$this->sm->tablesExist([Tables::zx_releases->name])) {
            $schema = new Schema();
            $prods = $schema->createTable(Tables::zx_releases->name);

            $prods->addColumn('id', 'integer')->setNotnull(true);
            $prods->setPrimaryKey(['id']);

            $prods->addColumn('title', 'string');
            $prods->addColumn('date_modified', 'integer');

            $platform = $this->connection->getDatabasePlatform();
            foreach ($schema->toSql($platform) as $sql) {
                $this->connection->executeStatement($sql);
            }
        }
    }
}
