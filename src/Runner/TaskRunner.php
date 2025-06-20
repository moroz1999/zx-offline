<?php
declare(strict_types=1);

namespace App\Runner;

use App\Sync\ZxProdsSyncService;
use App\Sync\ZxReleasesSyncService;
use App\Tasks\TaskException;
use App\Tasks\TaskRecord;
use App\Tasks\TasksRepository;
use App\Tasks\TaskStatuses;
use App\Tasks\TaskTypes;
use App\ZxReleases\ZxReleaseException;

readonly class TaskRunner
{
    public function __construct(
        private ZxProdsSyncService    $prodsSyncService,
        private ZxReleasesSyncService $zxReleasesSyncService,
        private TasksRepository       $tasksService,
    )
    {
    }

    /**
     * @throws TaskUnknownTypeException
     * @throws TaskRunnerException
     */
    public function run(string $taskId): TaskRecord
    {
        try {
            $task = $this->tasksService->getTaskById($taskId);

            $this->tasksService->updateTask($task->id, TaskStatuses::in_progress);
            match ($task->type) {
                TaskTypes::sync_prods->name => $this->runSyncProds(),
                TaskTypes::sync_releases->name => $this->runSyncReleases(),
                TaskTypes::check_prod_releases->name => $this->runCheckProdReleases((int)$task->targetId),
                default => throw new TaskUnknownTypeException("Unknown task type: $task->type"),
            };
            $this->tasksService->updateTask($taskId, TaskStatuses::done);
            return $task;
        } catch (TaskException $e) {
            throw new TaskRunnerException("Task running $taskId not found:" . $e->getMessage());
        }
    }

    /**
     * @throws TaskRunnerException
     */
    private function runSyncProds(): void
    {
        try {
            $this->prodsSyncService->sync();
            $this->tasksService->addTask(TaskTypes::sync_releases);
        } catch (TaskException $e) {
            throw new TaskRunnerException("Error adding {TaskTypes::sync_releases->name} task: " . $e->getMessage());
        }
    }

    /**
     * @throws TaskRunnerException
     */
    private function runCheckProdReleases(int $zxProdId): void
    {
        try {
            $this->zxReleasesSyncService->syncByProdId($zxProdId);
        } catch (ZxReleaseException $e) {
            throw new TaskRunnerException("Error adding {TaskTypes::sync_releases->name} task: " . $e->getMessage());
        }
    }


    private function runSyncReleases(): void
    {
        $this->zxReleasesSyncService->sync();
    }
}
