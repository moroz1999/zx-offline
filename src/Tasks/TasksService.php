<?php
declare(strict_types=1);

namespace App\Tasks;

use Illuminate\Database\Connection;

final readonly class TasksService
{
    public function __construct(
        private Connection $db,
    )
    {

    }

    public function getTask(): Task
    {
        $task = $this->db->table('tasks')->where('status', 'todo')->orderBy('created_at')->first();
        return $task;
    }

    public function lockTask(Task $task): void
    {
        $this->db->table('tasks')->where('id', $task->id)->update(['status' => 'in_progress']);
    }
}