<?php
declare(strict_types=1);

namespace App\Tasks;

use Doctrine\DBAL\Connection;

readonly class TasksService
{
    public function __construct(
        private Connection $db
    )
    {
    }

    public function getTask(): ?array
    {
        return $this->db->createQueryBuilder()
            ->select('*')
            ->from('tasks')
            ->where('status = :status')
            ->setParameter('status', 'todo')
            ->orderBy('created_at', 'ASC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative() ?: null;
    }

    public function lockTask(array $task): void
    {
        $this->db->update('tasks', [
            'status' => 'in_progress',
            'updated_at' => date('c'),
        ], ['id' => $task['id']]);
    }
}
