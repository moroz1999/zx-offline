<?php
declare(strict_types=1);

namespace App\Runner;

use App\Sync\ProdsSyncService;
use App\Sync\ReleasesSyncService;
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
    )
    {
    }

    /**
     * @throws UnknownTaskTypeException
     * @throws TaskNotFoundException
     */
    public function run(string $taskId, LoggerInterface $logger): TaskRecord
    {
        $task = $this->tasksService->getTaskById($taskId);
        if (!$task) {
            throw new TaskNotFoundException("TaskDto {$task->id} not found.");
        }
        $this->tasksService->updateTask($task->id, TaskStatuses::in_progress);

        match ($task->type) {
            'sync_prods' => $this->runSyncProds($task, $logger),
            'sync_releases' => $this->runSyncReleases($task, $logger),
            default => throw new UnknownTaskTypeException("Unknown task type: {$task->type}"),
        };
        $this->tasksService->updateTask($taskId, TaskStatuses::done);
        return $task;
    }

    private function runSyncProds(TaskRecord $task, LoggerInterface $logger): void
    {
        $this->prodsSyncService->sync();
        $this->tasksService->addTask(TaskTypes::sync_releases);
        $logger->notice("Task " . TaskTypes::sync_releases->name . " added.");
    }

    private function runSyncReleases(TaskRecord $task, LoggerInterface $logger): void
    {
        $this->releasesSyncService->sync();
    }
}
