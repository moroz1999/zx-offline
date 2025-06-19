<?php
declare(strict_types=1);

namespace App\Runner;

use App\Sync\ProdsSyncService;
use App\Sync\ReleasesSyncService;
use App\Tasks\TaskException;
use App\Tasks\TaskRecord;
use App\Tasks\TasksRepository;
use App\Tasks\TaskStatuses;
use App\Tasks\TaskTypes;
use Psr\Log\LoggerInterface;

readonly class TaskRunner
{
    public function __construct(
        private ProdsSyncService    $prodsSyncService,
        private ReleasesSyncService $releasesSyncService,
        private TasksRepository     $tasksService,
        private LoggerInterface $loggerHolder,
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
                'sync_prods' => $this->runSyncProds(),
                'sync_releases' => $this->runSyncReleases(),
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
        $this->loggerHolder->notice("Task " . TaskTypes::sync_releases->name . " added.");
    }

    private function runSyncReleases(): void
    {
        $this->releasesSyncService->sync();
    }
}
