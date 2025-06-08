<?php
declare(strict_types=1);

namespace App\DB\Interfaces;

interface MigrationInterface
{
    public function up(): void;
    public function down(): void;
}