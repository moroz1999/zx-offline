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

readonly class TaskRunner
{
    public function __construct(
        private ZxProdsSyncService    $prodsSyncService,
        private ZxReleasesSyncService $releasesSyncService,
        private TasksRepository       $tasksService,
    )
    {
    }

    /**
     * @throws TaskUnknownTypeException
     * @throws TaskRunningException
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
            throw new TaskRunningException("Task running $taskId not found:" . $e->getMessage());
        }
    }

    /**
     * @throws TaskRunningException
     */
    private function runSyncProds(): void
    {
        try {
            $this->prodsSyncService->sync();
            $this->tasksService->addTask(TaskTypes::sync_releases);
        } catch (TaskException $e) {
            throw new TaskRunningException("Error adding {TaskTypes::sync_releases->name} task: " . $e->getMessage());
        }
    }

    /**
     * @throws TaskRunningException
     */
    private function runCheckProdReleases(int $zxProdId): void
    {
        try {
            $this->releasesSyncService->syncByProdId($zxProdId);;
        } catch (TaskException $e) {
            throw new TaskRunningException("Error adding {TaskTypes::sync_releases->name} task: " . $e->getMessage());
        }
    }


    private function runSyncReleases(): void
    {
        $this->releasesSyncService->sync();
    }
}
