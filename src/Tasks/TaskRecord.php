<?php
declare(strict_types=1);


namespace App\Tasks;

final readonly class TaskRecord
{
    public function __construct(
        public string  $id,
        public string  $type,
        public ?string $targetId,
        public string  $status,
        public int     $attempts,
        public ?string $lastError,
        public string  $createdAt,
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['type'],
            $data['target_id'],
            $data['status'],
            (int)$data['attempts'],
            $data['last_error'],
            $data['created_at'],
        );
    }
}
