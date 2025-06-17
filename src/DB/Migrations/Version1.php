<?php
declare(strict_types=1);


namespace App\DB\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version1 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Database for storing tasks and synced data';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE example_table (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE example_table');
    }
}