<?php
declare(strict_types=1);

namespace App\DB\Migrations;

final readonly class Schema
{
    public function __construct(
        private array $migrations,
    )
    {
    }

    public function up(): void
    {
        foreach ($this->migrations as $migration) {
            $migration->up();
        }
    }
}
