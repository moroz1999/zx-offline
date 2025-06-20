<?php
declare(strict_types=1);

namespace App\DB;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Psr\Log\LoggerInterface;

readonly class SchemaService
{
    private AbstractSchemaManager $sm;
    private AbstractPlatform $platform;

    /**
     * @throws Exception
     */
    public function __construct(
        private Connection      $connection,
        private LoggerInterface $logger,
    )
    {
        $this->sm = $connection->createSchemaManager();
        $this->platform = $connection->getDatabasePlatform();
    }

    /**
     * @throws SchemaException
     */
    public function dropBase(): void
    {
        try {
            $this->dropTable(Tables::files);
            $this->dropTable(Tables::zx_releases);
            $this->dropTable(Tables::zx_prods);
            $this->dropTable(Tables::tasks);
        } catch (Exception $e) {
            throw new SchemaException("Error dropping database schema: {$e->getMessage()} {$e->getTraceAsString()}");
        }
    }

    /**
     * @throws Exception
     */
    private function dropTable(Tables $tableName): void
    {
        if ($this->sm->tableExists($tableName->name)) {
            $this->sm->dropTable($tableName->name);
            $this->logger->info("$tableName->name table dropped.");
        }
    }

    /**
     * @throws SchemaException
     */
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

    /**
     * @throws Exception
     */
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

            $tasks->setPrimaryKey(['id']);
            $tasks->addIndex(['status'], 'idx_tasks_status');

            $this->executeSchema($schema);

            $this->logger->info('Tasks table created.');
        }
    }

    private function checkProdsTable(): void
    {
        if (!$this->sm->tableExists(Tables::zx_prods->name)) {
            $schema = new Schema();
            $prods = $schema->createTable(Tables::zx_prods->name);

            $prods->addColumn('id', 'integer')->setNotnull(true);

            $prods->addColumn('title', 'string');
            $prods->addColumn('date_modified', 'integer');
            $prods->addColumn('legal_status', 'string', ['notnull' => false]);
            $prods->addColumn('category_id', 'integer', ['notnull' => false]);
            $prods->addColumn('category_title', 'string', ['notnull' => false]);

            $prods->setPrimaryKey(['id']);

            $this->executeSchema($schema);

            $this->logger->info('Prods table created.');

        }
    }

    private function checkReleasesTable(): void
    {
        if (!$this->sm->tableExists(Tables::zx_releases->name)) {
            $schema = new Schema();
            $releases = $schema->createTable(Tables::zx_releases->name);

            $releases->addColumn('id', 'integer')->setNotnull(true);
            $releases->addColumn('title', 'string');
            $releases->addColumn('date_modified', 'integer');

            $releases->setPrimaryKey(['id']);

            $this->executeSchema($schema);
            $this->logger->info('Releases table created.');
        }
    }

    private function checkFilesTable(): void
    {
        if (!$this->sm->tableExists(Tables::files->name)) {
            $schema = new Schema();
            $files = $schema->createTable(Tables::files->name);

            $files->addColumn('id', 'integer')->setNotnull(true);
            $files->addColumn('zx_release_id', 'integer')->setNotnull(true);
            $files->addColumn('md5', 'string');
            $files->addColumn('type', 'string');
            $files->addColumn('file_path', 'string');

            $files->setPrimaryKey(['id']);
            $files->addIndex(['zx_release_id'], 'idx_files_release');

            $this->executeSchema($schema);
            $this->logger->info('Files table created.');
        }
    }

    /**
     * @throws Exception
     */
    private function executeSchema(Schema $schema): void
    {
        $queries = $schema->toSql($this->platform);
        foreach ($queries as $sql) {
            $this->connection->executeStatement($sql);
        }
    }
}
