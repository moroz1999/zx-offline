<?php
declare(strict_types=1);

namespace App\ZxReleases;

use App\DB\Tables;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class ZxReleasesRepository
{
    public function __construct(
        private Connection $db,
    )
    {
    }

    /**
     * @return int[]
     * @throws ZxReleaseException
     */
    public function getAllIds(): array
    {
        try {
            return $this->db->createQueryBuilder()
                ->select('id')
                ->from(Tables::zx_releases->name)
                ->executeQuery()
                ->fetchFirstColumn();
        } catch (Exception $e) {
            throw new ZxReleaseException($e->getMessage());
        }
    }

    /**
     * @throws ZxReleaseException
     */
    public function getById(int $id): ?ZxReleaseRecord
    {
        try {
            $row = $this->db->createQueryBuilder()
                ->select('*')
                ->from(Tables::zx_releases->name)
                ->where('id = :id')
                ->setParameter('id', $id)
                ->executeQuery()
                ->fetchAssociative();

            return $row ? new ZxReleaseRecord(
                id: $row['id'],
                title: $row['title'],
                dateModified: $row['date_modified'],
            ) : null;
        } catch (Exception $e) {
            throw new ZxReleaseException($e->getMessage());
        }
    }

    /**
     * @throws ZxReleaseException
     */
    public function create(ZxReleaseRecord $data): void
    {
        try {
            $this->db->insert(Tables::zx_releases->name, [
                'id' => $data->id,
                'title' => $data->title,
                'date_modified' => $data->dateModified,
            ]);
        } catch (Exception $e) {
            throw new ZxReleaseException($e->getMessage());
        }
    }

    /**
     * @throws ZxReleaseException
     */
    public function update(ZxReleaseRecord $data): void
    {
        try {
            $this->db->update(Tables::zx_releases->name, [
                'title' => $data->title,
                'date_modified' => $data->dateModified,
            ], ['id' => $data->id]);
        } catch (Exception $e) {
            throw new ZxReleaseException($e->getMessage());
        }
    }

    /**
     * @throws ZxReleaseException
     */
    public function delete(int $id): void
    {
        try {
            $this->db->delete(Tables::zx_releases->name, ['id' => $id]);
        } catch (Exception $e) {
            throw new ZxReleaseException($e->getMessage());
        }
    }
}