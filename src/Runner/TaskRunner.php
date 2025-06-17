<?php
declare(strict_types=1);

namespace App\Runner;

use App\Sync\ProductSyncService;
use Doctrine\DBAL\Connection;
use RuntimeException;

readonly class TaskRunner
{
    public function __construct(
        private Connection $db,
        private ProductSyncService $syncService,
    ) {}

    public function run(string $taskId): void
    {
        $task = $this->db->createQueryBuilder()
            ->select('*')
            ->from('tasks')
            ->where('id = :id')
            ->setParameter('id', $taskId)
            ->executeQuery()
            ->fetchAssociative();

        if (!$task) {
            throw new RuntimeException("Task {$taskId} not found.");
        }

        switch ($task['type']) {
            case 'sync_prods':
                $this->runSyncProds($task);
                break;

            default:
                throw new RuntimeException("Unknown task type: {$task['type']}");
        }

        $this->db->update('tasks', [
            'status' => 'done',
            'updated_at' => date('c'),
        ], ['id' => $taskId]);
    }

    private function runSyncProds(array $task): void
    {
        $this->syncService->sync();
    }
}
