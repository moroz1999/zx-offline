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

            return $row ? $this->mapRowToRecord($row) : null;
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
                'prod_id' => $data->prodId,
                'title' => $data->title,
                'date_modified' => $data->dateModified,
                'year' => $data->year,
                'release_type' => $data->releaseType,
                'version' => $data->version,
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
                'prod_id' => $data->prodId,
                'title' => $data->title,
                'date_modified' => $data->dateModified,
                'year' => $data->year,
                'release_type' => $data->releaseType,
                'version' => $data->version,
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

    /**
     * @return ZxReleaseRecord[]
     * @throws ZxReleaseException
     */
    public function getByProdId(int $prodId): array
    {
        try {
            $rows = $this->db->createQueryBuilder()
                ->select('*')
                ->from(Tables::zx_releases->name)
                ->where('prod_id = :prod_id')
                ->setParameter('prod_id', $prodId)
                ->executeQuery()
                ->fetchAllAssociative();

            return array_map(
                fn(array $row) => $this->mapRowToRecord($row),
                $rows
            );
        } catch (Exception $e) {
            throw new ZxReleaseException($e->getMessage());
        }
    }

    private function mapRowToRecord(array $row): ZxReleaseRecord
    {
        return new ZxReleaseRecord(
            id: $row['id'],
            prodId: $row['prod_id'],
            title: $row['title'],
            dateModified: $row['date_modified'],
            year: $row['year'],
            releaseType: $row['release_type'],
            version: $row['version'],
        );
    }
}