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

    /** @return int[]
     * @throws Exception
     */
    public function getAllIds(): array
    {
        return $this->db->createQueryBuilder()
            ->select('id')
            ->from('prods')
            ->executeQuery()
            ->fetchFirstColumn();
    }

    public function getById(int $id): ?ZxProdRecord
    {
        $row = $this->db->createQueryBuilder()
            ->select('*')
            ->from('prods')
            ->where('id = :id')
            ->setParameter('id', $id)
            ->executeQuery()
            ->fetchAssociative();

        return $row ? new ZxProdRecord(...$row) : null;
    }

    public function create(ZxProdRecord $data): void
    {
        $this->db->insert('prods', [
            'id' => $data->id,
            'title' => $data->title,
            'date_modified' => $data->dateModified,
            'legal_status' => $data->legalStatus,
            'category_id' => $data->categoryId,
            'category_title' => $data->categoryTitle,
        ]);
    }

    public function update(ZxProdRecord $data): void
    {
        $this->db->update('prods', [
            'title' => $data->title,
            'date_modified' => $data->dateModified,
            'legal_status' => $data->legalStatus,
            'category_id' => $data->categoryId,
            'category_title' => $data->categoryTitle,
        ], ['id' => $data->id]);
    }

    public function delete(int $id): void
    {
        $this->db->delete('prods', ['id' => $id]);
    }
}
