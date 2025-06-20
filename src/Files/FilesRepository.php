<?php
declare(strict_types=1);

namespace App\Files;

use App\DB\Tables;
use Doctrine\DBAL\Connection;

final readonly class FilesRepository
{
    public function __construct(
        private Connection $db,
    )
    {
    }

    /**
     * @return int[]
     */
    public function getAllIds(): array
    {
        return $this->db->createQueryBuilder()
            ->select('id')
            ->from(Tables::files->name)
            ->executeQuery()
            ->fetchFirstColumn();
    }

    public function getById(int $id): ?FileRecord
    {
        $row = $this->db->createQueryBuilder()
            ->select('*')
            ->from(Tables::files->name)
            ->where('id = :id')
            ->setParameter('id', $id)
            ->executeQuery()
            ->fetchAssociative();

        return $row ? $this->createDtoFromRow($row) : null;
    }

    public function create(FileRecord $data): void
    {
        $this->db->insert(Tables::files->name, [
            'id' => $data->id,
            'md5' => $data->md5,
            'type' => $data->type,
            'zx_release_id' => $data->zxReleaseId,
            'file_path' => $data->filePath,
        ]);
    }

    public function update(FileRecord $data): void
    {
        $this->db->update(Tables::files->name, [
            'md5' => $data->md5,
            'type' => $data->type,
            'zx_release_id' => $data->zxReleaseId,
            'file_path' => $data->filePath,
        ], ['id' => $data->id]);
    }

    public function delete(int $id): void
    {
        $this->db->delete(Tables::files->name, ['id' => $id]);
    }

    /**
     * @return FileRecord[]
     */
    public function getByReleaseId(int $releaseId): array
    {
        $rows = $this->db->createQueryBuilder()
            ->select('*')
            ->from(Tables::files->name)
            ->where('zx_release_id = :rel')
            ->setParameter('rel', $releaseId)
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map(fn($row) => $this->createDtoFromRow($row), $rows);
    }

    private function createDtoFromRow(array $row): FileRecord
    {
        return new FileRecord(
            id: $row['id'],
            zxReleaseId: $row['zx_release_id'],
            md5: $row['md5'],
            type: $row['type'],
            filePath: $row['file_path'],
        );
    }
}