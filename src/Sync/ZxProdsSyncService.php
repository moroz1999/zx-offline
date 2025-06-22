<?php
declare(strict_types=1);

namespace App\Sync;

use App\Api\ZxArtApiProdsRequester;
use App\Api\ZxProdApiDto;
use App\Tasks\TasksRepository;
use App\Tasks\TaskTypes;
use App\ZxProds\ZxProdRecord;
use App\ZxProds\ZxProdsRepository;
use Psr\Log\LoggerInterface;

final readonly class ZxProdsSyncService
{
    public function __construct(
        private ZxArtApiProdsRequester $prodsApi,
        private ZxProdsRepository      $zxProdsRepository,
        private TasksRepository        $tasks,
        private LoggerInterface        $logger,
    )
    {
    }

    public function sync(): void
    {
        $existingIds = array_flip($this->zxProdsRepository->getAllIds());

        foreach ($this->prodsApi->getAll() as $apiProd) {
            $record = $this->mapToRecord($apiProd);

            $existing = $this->zxProdsRepository->getById($record->id);
            unset($existingIds[$record->id]);

            if (!$existing) {
                $this->zxProdsRepository->create($record);
                $this->logger->info("Prod $record->id created");
                continue;
            }

            if ($record->dateModified > $existing->dateModified) {
                $this->zxProdsRepository->update($record);
                $this->tasks->addTask(TaskTypes::check_prod_releases, (string)$record->id);
                $this->logger->info("Prod $record->id updated");
            }
        }

        foreach (array_keys($existingIds) as $obsoleteId) {
            $this->zxProdsRepository->delete($obsoleteId);
            $this->logger->info("Prod $obsoleteId deleted as removed from API");
        }
    }

    private function mapToRecord(ZxProdApiDto $dto): ZxProdRecord
    {
        $cat = $dto->categories[0] ?? null;

        return new ZxProdRecord(
            id: $dto->id,
            title: $dto->title,
            dateModified: $dto->dateModified,
            legalStatus: $dto->legalStatus,
            categoryId: $cat?->id,
            categoryTitle: $cat?->title,
            year: $dto->year,
        );
    }
}
