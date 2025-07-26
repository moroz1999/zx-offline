<?php
declare(strict_types=1);

namespace App\Files;

use App\DB\Tables;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;

final readonly class FilePathsRepository
{
    public function __construct(
        private Connection $db,
    )
    {
    }

    /**
     * @return FilePathRecord[]
     */
    public function getByFileId(int $fileId): array
    {
        $rows = $this->db->createQueryBuilder()
            ->select('*')
            ->from(Tables::file_paths->name)
            ->where('file_id = :fid')
            ->setParameter('fid', $fileId)
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map($this->createDtoFromRow(...), $rows);
    }

    public function create(FilePathRecord $record): void
    {
        $this->db->insert(Tables::file_paths->name, [
            'id' => $record->id->toString(),
            'file_id' => $record->fileId,
            'file_path' => $record->filePath,
        ]);
    }


    public function deleteByFileId(int $fileId): void
    {
        $this->db->delete(Tables::file_paths->name, ['file_id' => $fileId]);
    }

    public function update(FilePathRecord $data): void
    {
        $this->db->update(Tables::file_paths->name, [
            'file_id' => $data->fileId,
            'file_path' => $data->filePath,
        ], ['id' => $data->id]);
    }

    public function delete(int $id): void
    {
        $this->db->delete(Tables::file_paths->name, ['id' => $id]);
    }

    private function createDtoFromRow(array $row): FilePathRecord
    {
        return new FilePathRecord(
            id: Uuid::fromString($row['id']),
            fileId: (int)$row['file_id'],
            filePath: $row['file_path'],
        );
    }
}
