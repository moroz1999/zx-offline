<?php
declare(strict_types=1);

namespace App\Sync;

use App\Api\ZxArtApiProdsRequester;
use App\Api\ZxProdDto;
use App\Tasks\TasksRepository;
use App\Tasks\TaskTypes;
use App\ZxProds\ZxProdRecord;
use App\ZxProds\ZxProdsRepository;
use Psr\Log\LoggerInterface;

final readonly class ProdsSyncService
{
    public function __construct(
        private ZxArtApiProdsRequester $prodsApi,
        private ZxProdsRepository      $prodsRepo,
        private TasksRepository        $tasks,
        private LoggerInterface        $logger,
    )
    {
    }

    public function sync(): void
    {
        $existingIds = array_flip($this->prodsRepo->getAllIds());

        foreach ($this->prodsApi->getAll() as $apiProd) {
            $record = $this->mapToRecord($apiProd);

            $existing = $this->prodsRepo->getById($record->id);
            unset($existingIds[$record->id]);

            if (!$existing) {
                $this->prodsRepo->create($record);
                $this->logger->info("Prod $record->id created");
                continue;
            }

            if ($record->dateModified > $existing->dateModified) {
                $this->prodsRepo->update($record);
                $this->tasks->addTask(TaskTypes::sync_releases, (string)$record->id);
                $this->logger->info("Prod $record->id updated");
            }
        }

        foreach (array_keys($existingIds) as $obsoleteId) {
            $this->prodsRepo->delete($obsoleteId);
            $this->logger->info("Prod $obsoleteId deleted as removed from API");
        }
    }

    private function mapToRecord(ZxProdDto $dto): ZxProdRecord
    {
        $cat = $dto->categories[0] ?? null;

        return new ZxProdRecord(
            id: $dto->id,
            title: $dto->title,
            dateModified: $dto->dateModified,
            legalStatus: $dto->legalStatus,
            categoryId: $cat?->id,
            categoryTitle: $cat?->title,
        );
    }
}
