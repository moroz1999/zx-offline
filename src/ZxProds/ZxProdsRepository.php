<?php
declare(strict_types=1);

namespace App\ZxProds;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class ZxProdsRepository
{
    public function __construct(
        private Connection $db,
    )
    {
    }

    /**
     * @return int[]
     * @throws ZxProdException
     */
    public function getAllIds(): array
    {
        try {
            return $this->db->createQueryBuilder()
                ->select('id')
                ->from('prods')
                ->executeQuery()
                ->fetchFirstColumn();
        } catch (Exception $e) {
            throw new ZxProdException($e->getMessage());
        }
    }

    /**
     * @throws ZxProdException
     */
    public function getById(int $id): ?ZxProdRecord
    {
        try {
            $row = $this->db->createQueryBuilder()
                ->select('*')
                ->from('prods')
                ->where('id = :id')
                ->setParameter('id', $id)
                ->executeQuery()
                ->fetchAssociative();

            return $row ? new ZxProdRecord(
                id: $row['id'],
                title: $row['title'],
                dateModified: $row['date_modified'],
                legalStatus: $row['legal_status'],
                categoryId: $row['category_id'],
                categoryTitle: $row['category_title'],
            ) : null;
        } catch (Exception $e) {
            throw new ZxProdException($e->getMessage());
        }
    }

    /**
     * @throws ZxProdException
     */
    public function create(ZxProdRecord $data): void
    {
        try {
            $this->db->insert('prods', [
                'id' => $data->id,
                'title' => $data->title,
                'date_modified' => $data->dateModified,
                'legal_status' => $data->legalStatus,
                'category_id' => $data->categoryId,
                'category_title' => $data->categoryTitle,
            ]);
        } catch (Exception $e) {
            throw new ZxProdException($e->getMessage());
        }
    }

    /**
     * @throws ZxProdException
     */
    public function update(ZxProdRecord $data): void
    {
        try {
            $this->db->update('prods', [
                'title' => $data->title,
                'date_modified' => $data->dateModified,
                'legal_status' => $data->legalStatus,
                'category_id' => $data->categoryId,
                'category_title' => $data->categoryTitle,
            ], ['id' => $data->id]);
        } catch (Exception $e) {
            throw new ZxProdException($e->getMessage());
        }
    }

    /**
     * @throws ZxProdException
     */
    public function delete(int $id): void
    {
        try {
            $this->db->delete('prods', ['id' => $id]);
        } catch (Exception $e) {
            throw new ZxProdException($e->getMessage());
        }
    }
}