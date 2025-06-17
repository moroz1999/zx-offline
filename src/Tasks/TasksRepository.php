<?php
declare(strict_types=1);

namespace App\Tasks;

use Doctrine\DBAL\Connection;
use Throwable;

readonly class TasksRepository
{
    public function __construct(
        private Connection $db
    )
    {
    }

    /**
     * @throws TaskException
     */
    public function getNextTask(): ?TaskRecord
    {
        try {
            $row = $this->db->createQueryBuilder()
                ->select('*')
                ->from('tasks')
                ->where('status = :status')
                ->setParameter('status', TaskStatuses::todo->name)
                ->orderBy('created_at', 'ASC')
                ->setMaxResults(1)
                ->executeQuery()
                ->fetchAssociative();

            return $row ? TaskRecord::fromArray($row) : null;
        } catch (Throwable $e) {
            throw new TaskException("Error getting task: {$e->getMessage()}");
        }
    }

    /**
     * @throws TaskException
     */
    public function lockTask(TaskRecord $task): void
    {
        try {
            $this->db->update('tasks', [
                'status' => TaskStatuses::in_progress->name,
                'updated_at' => date('c'),
            ], ['id' => $task->id]);
        } catch (Throwable $e) {
            throw new TaskException("Error locking task: {$e->getMessage()}");
        }
    }

    /**
     * @throws TaskException
     */
    public function getTaskById(string $taskId): TaskRecord
    {
        try {
            $row = $this->db->createQueryBuilder()
                ->select('*')
                ->from('tasks')
                ->where('id = :id')
                ->setParameter('id', $taskId)
                ->executeQuery()
                ->fetchAssociative();
            return TaskRecord::fromArray($row);
        } catch (Throwable $e) {
            throw new TaskException("Error getting task $taskId: {$e->getMessage()}");
        }
    }

    public function updateTask(string $taskId, TaskStatuses $status): void
    {
        try {
            $this->db->update('tasks', [
                'status' => $status->name,
                'updated_at' => date('c'),
            ], ['id' => $taskId]);
        } catch (Throwable $e) {
            throw new TaskException("Error updating task $taskId: {$e->getMessage()}");
        }
    }

    /**
     * @throws TaskException
     */
    public function addTask(TaskTypes $type, ?string $targetId = null): void
    {
        try {
            $this->db->insert('tasks', [
                'id' => uniqid('', true),
                'type' => $type->name,
                'status' => TaskStatuses::todo->name,
                'created_at' => date('c'),
                'updated_at' => date('c'),
                'target_id' => $targetId,
            ]);
        } catch (Throwable $e) {
            throw new TaskException("Error creating task: {$e->getMessage()}");
        }
    }
}
