<?php
declare(strict_types=1);

namespace App\Runner;

use Illuminate\Database\Capsule\Manager as DB;

class TaskRunner
{
    public function run(string $taskId): void
    {
        $task = DB::table('tasks')->where('id', $taskId)->first();

        if (!$task) {
            throw new \RuntimeException("Task {$taskId} not found.");
        }

        switch ($task->type) {
            case 'sync_prods':
                $this->runSyncProds($task);
                break;

            default:
                throw new \RuntimeException("Unknown task type: {$task->type}");
        }

        DB::table('tasks')->where('id', $taskId)->update([
            'status' => 'done',
            'updated_at' => date('c'),
        ]);
    }

    protected function runSyncProds(object $task): void
    {
        $syncService = new \App\Sync\ProductSyncService();
        $syncService->sync();
    }
}
