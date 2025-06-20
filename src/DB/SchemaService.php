<?php
declare(strict_types=1);

namespace App\DB;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;

readonly class SchemaService
{
    private AbstractSchemaManager $sm;
    private AbstractPlatform $platform;

    public function __construct(
        private Connection $connection,
    ) {
        $this->sm = $connection->createSchemaManager();
        $this->platform = $connection->getDatabasePlatform();
    }

    public function dropBase(): void
    {
        try {
            $this->dropTable(Tables::files);
            $this->dropTable(Tables::zx_releases);
            $this->dropTable(Tables::zx_prods);
            $this->dropTable(Tables::tasks);
        } catch (Exception $e) {
            throw new SchemaException("Error dropping database schema: {$e->getMessage()}");
        }
    }

    private function dropTable(Tables $tableName): void
    {
        if ($this->sm->tablesExist([$tableName->name])) {
            $schema = new Schema();
            $schema->dropTable($tableName->name);
            $this->executeSchema($schema);
        }
    }

    public function createIfNeeded(): void
    {
        try {
            $this->checkTasksTable();
            $this->checkProdsTable();
            $this->checkReleasesTable();
            $this->checkFilesTable();
        } catch (Exception $e) {
            throw new SchemaException("Error creating database schema: {$e->getMessage()}");
        }
    }

    private function checkTasksTable(): void
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

            $this->executeSchema($schema);
        }
    }

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

            $this->executeSchema($schema);
        }
    }

    private function checkReleasesTable(): void
    {
        if (!$this->sm->tablesExist([Tables::zx_releases->name])) {
            $schema = new Schema();
            $releases = $schema->createTable(Tables::zx_releases->name);

            $releases->addColumn('id', 'integer')->setNotnull(true);
            $releases->setPrimaryKey(['id']);

            $releases->addColumn('title', 'string');
            $releases->addColumn('date_modified', 'integer');

            $this->executeSchema($schema);
        }
    }

    private function checkFilesTable(): void
    {
        if (!$this->sm->tablesExist([Tables::files->name])) {
            $schema = new Schema();
            $files = $schema->createTable(Tables::files->name);

            $files->addColumn('id', 'integer')->setNotnull(true);
            $files->setPrimaryKey(['id']);

            $files->addColumn('zx_release_id', 'integer')->setNotnull(true);
            $files->addColumn('md5', 'string');
            $files->addColumn('type', 'string');
            $files->addColumn('file_name', 'string');

            $this->executeSchema($schema);
        }
    }

    private function executeSchema(Schema $schema): void
    {
        $queries = $schema->toSql($this->platform);
        foreach ($queries as $sql) {
            $this->connection->executeStatement($sql);
        }
    }
}
