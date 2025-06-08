<?php
declare(strict_types=1);

namespace App\DB\Migrations;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;
use App\DB\Interfaces\MigrationInterface;

class TasksTableMigration implements MigrationInterface
{
    public function up(): void
    {
        $schema = Capsule::schema();

        if (!$schema->hasTable('tasks')) {
            $schema->create('tasks', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('type');
                $table->string('target_id')->nullable();
                $table->string('status')->default('todo');
                $table->integer('attempts')->default(0);
                $table->text('last_error')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        $schema = Capsule::schema();

        if ($schema->hasTable('tasks')) {
            $schema->dropIfExists('tasks');
        }
    }
}